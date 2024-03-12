<?php

namespace WeDevs\Dokan\Test\Commission;

use WeDevs\Dokan\Commission\Calculators\CategoryBasedCommissionCalculator;
use WeDevs\Dokan\Commission\Calculators\CombineCommissionCalculator;
use WeDevs\Dokan\Commission\Calculators\FixedCommissionCalculator;
use WeDevs\Dokan\Commission\Calculators\FlatCommissionCalculator;
use WeDevs\Dokan\Commission\Calculators\PercentageCommissionCalculator;
use WeDevs\Dokan\Commission\CommissionContext;
use WeDevs\Dokan\Commission\Strategies\GlobalCommissionSourceStrategy;
use WeDevs\Dokan\Commission\Strategies\OrderItemCommissionSourceStrategy;
use WeDevs\Dokan\Commission\Strategies\ProductCommissionSourceStrategy;
use WeDevs\Dokan\Commission\Strategies\VendorCommissionSourceStrategy;
use WeDevs\Dokan\ProductCategory\Helper;
use WeDevs\Dokan\Test\Helpers\WC_Helper_Order;
use WeDevs\Dokan\Test\Helpers\WC_Helper_Product;
use WP_UnitTestCase;

class CommissionTest extends WP_UnitTestCase {
    private array $category_ids;

    /**
     * Set up
     *
     * @return void
     */
    public function set_up() {
        parent::set_up();

        $categories = [
            'Category_1',
            'Category_2',
            'Category_3',
            'Category_4',
            'Category_5',
            'Category_6',
            'Category_7',
            'Category_8',
            'Category_9',
            'Category_10',
        ];

        $ids = [];

        foreach ( $categories as $category_name ) {
            if ( ! term_exists( $category_name, 'product_cat' ) ) {
                $term = wp_insert_term(
                    $category_name, // the term
                    'product_cat',  // the taxonomy
                    [
                        'description' => 'Description for ' . $category_name,
                        'slug'        => sanitize_title( $category_name ),
                    ]
                );

                $ids[] = $term['term_id'];
            }
        }

        $this->category_ids = $ids;
    }

    /**
     * Test the calculation with no data saved.
     *
     * @since DOKAN_SINCE
     *
     * @test
     *
     * @return void
     */
    public function test_that_we_can_get_commission_with_non_existed_product_and_vendor() {
        $orderItemId = 1; // Example IDs
        $productId   = 103;
        $vendorId    = 2;
        $category_id = 15;     // Example cat

        $strategies = [
            new OrderItemCommissionSourceStrategy( $orderItemId ),
            new ProductCommissionSourceStrategy( $productId ),
            new VendorCommissionSourceStrategy( $vendorId, $category_id ),
            new GlobalCommissionSourceStrategy( $category_id ),
        ];

        $context      = new CommissionContext( $strategies );
        $productPrice = 100.00; // Example product price
        $commission   = $context->calculate_commission( $productPrice, 1 );

        $this->assertTrue( is_a( $commission, 'WeDevs\Dokan\Commission\Utils\CommissionData' ) );
        $this->assertIsArray( $commission->get_data() );
        $this->assertEquals( 'none', $commission->get_source() );
        $this->assertEquals( 0, $commission->get_per_item_admin_commission() );
        $this->assertEquals( 0, $commission->get_admin_commission() );
        $this->assertEquals( $productPrice, $commission->get_vendor_earning() );
        $this->assertEquals( 1, $commission->get_total_quantity() );
        $this->assertEquals( $productPrice, $commission->get_total_amount() );
        $this->assertEquals( 'none', $commission->get_type() );
        $this->assertEquals( [], $commission->get_parameters() );
    }

    /**
     * @since DOKAN_SINCE
     *
     * @test
     *
     * @throws \WC_Data_Exception
     *
     * @return void
     */
    public function test_fixed_commission_calculation_works_properly_for_smaller_amount_then_commission_amount_in_global() {
        $commission_global_options = [
            "shipping_fee_recipient"           => "admin",
            "tax_fee_recipient"                => "admin",
            "shipping_tax_fee_recipient"       => "admin",
            "new_seller_enable_selling"        => "on",
            "commission_type"                  => "fixed",
            "admin_percentage"                 => "5",
            "additional_fee"                   => "5",
            "order_status_change"              => "on",
            "commission_category_based_values" => [
                "all"   => [
                    "flat"       => "",
                    "percentage" => "",
                ],
                "items" => [
                    $this->category_ids[0] => [
                        "flat"       => "5",
                        "percentage" => "10",
                    ],
                ],
            ],
        ];
        update_option( 'dokan_selling', $commission_global_options );

        $vendor = dokan()->vendor->create(
            [
                'user_login' => 'custom-vendor-' . rand(),
            ],
        );
        wp_set_current_user( $vendor->get_id() );

        $product = dokan()->product->create(
            [
                'name'          => 'custom_product_' . rand(),
                'regular_price' => 2,
                'categories'    => $this->category_ids,
            ]
        );

        dokan_override_product_author( $product, $vendor->get_id() );

        $chosen_cat = Helper::get_saved_products_category( $product->get_id() )['chosen_cat'];
        Helper::generate_and_set_chosen_categories( $product->get_id(), $chosen_cat );

        $vendor_earning   = dokan()->commission->get_earning_by_product( $product, 'seller' );
        $admin_commission = dokan()->commission->get_earning_by_product( $product, 'admin' );

        $this->assertEquals( 0, $vendor_earning );
        $this->assertEquals( 2, $admin_commission );
    }

    /**
     * @since DOKAN_SINCE
     *
     * @test
     *
     * @return void
     */
    public function test_that_we_can_get_the_legacy_commission_types() {
        $this->assertTrue( method_exists( dokan()->commission, 'get_legacy_commission_types' ) );

        $legacy_commission_types = dokan()->commission->get_legacy_commission_types();

        $this->assertSame(
            array_keys( $legacy_commission_types ),
            [
                'combine',
                'percentage',
                'flat',
            ]
        );
    }

    public function product_settings_data_provider() {
        return [
            [
                [
                    'data_set'        => 1,
                    'product_setting' => [
                        'percentage' => 5,
                        'type'       => 'fixed',
                        'flat'       => 5,
                    ],
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => "fixed",
                        "admin_percentage"                 => "5",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [
                            "all"   => [
                                "flat"       => "",
                                "percentage" => "",
                            ],
                            "items" => [
                                'category_1' => [
                                    "flat"       => "5",
                                    "percentage" => "10",
                                ],
                            ],
                        ],
                    ],
                    'vendor_settings' => [
                        'dokan_admin_percentage'      => 5,
                        'dokan_admin_percentage_type' => 'fixed',
                        'dokan_admin_additional_fee'  => 5,
                        'category_commissions'        => [
                            "all"   => [
                                "flat"       => "",
                                "percentage" => "",
                            ],
                            "items" => [
                                'category_2' => [
                                    "flat"       => "5",
                                    "percentage" => "10",
                                ],
                            ],
                        ],
                    ],
                    'category_id'     => 'category_2',
                    'total_price'     => 150,
                    'total_quantity'  => 1,

                ],
                [
                    'strategy_source'           => ProductCommissionSourceStrategy::SOURCE,
                    'calculator_source'         => FixedCommissionCalculator::SOURCE,
                    'is_applicable'             => true,
                    'admin_commission'          => 12.5,
                    'per_item_admin_commission' => 12.5,
                    'vendor_earning'            => 137.5,
                    'total_quantity'            => 1,
                ],
            ],
            [
                [
                    'data_set'        => 2,
                    'product_setting' => '',
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => "fixed",
                        "admin_percentage"                 => "5",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [
                            "all"   => [
                                "flat"       => "",
                                "percentage" => "",
                            ],
                            "items" => [
                                'category_0' => [
                                    "flat"       => "5",
                                    "percentage" => "10",
                                ],
                            ],
                        ],
                    ],
                    'vendor_settings' => [
                        'percentage'           => 5,
                        'type'                 => CategoryBasedCommissionCalculator::SOURCE,
                        'flat'                 => 5,
                        'category_commissions' => [
                            "all"   => [
                                "flat"       => "",
                                "percentage" => "",
                            ],
                            "items" => [
                                'category_3' => [
                                    "flat"       => "5",
                                    "percentage" => "10",
                                ],
                            ],
                        ],
                    ],
                    'category_id'     => 'category_3',
                    'total_price'     => 300,
                ],
                [
                    'is_applicable'             => true,
                    'calculator_source'         => CategoryBasedCommissionCalculator::SOURCE,
                    'strategy_source'           => VendorCommissionSourceStrategy::SOURCE,
                    'admin_commission'          => 35,
                    'per_item_admin_commission' => 15,
                    'vendor_earning'            => 265,
                ],
            ],
            [
                [
                    'data_set'        => 3,
                    'product_setting' => '',
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => "fixed",
                        "admin_percentage"                 => "10",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [
                            "all"   => [
                                "flat"       => "",
                                "percentage" => "",
                            ],
                            "items" => [
                                'category_4' => [
                                    "flat"       => "5",
                                    "percentage" => "10",
                                ],
                            ],
                        ],
                    ],
                    'vendor_settings' => [],
                    'category_id'     => 'category_4',
                    'total_price'     => 300,
                ],
                [
                    'is_applicable'             => true,
                    'calculator_source'         => FixedCommissionCalculator::SOURCE,
                    'strategy_source'           => GlobalCommissionSourceStrategy::SOURCE,
                    'admin_commission'          => 35,
                    'per_item_admin_commission' => 15,
                    'vendor_earning'            => 265,
                ],
            ],






            [
                [
                    'data_set'        => 4,
                    'product_setting' => '',
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => FlatCommissionCalculator::SOURCE,
                        "admin_percentage"                 => "10",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [],
                    ],
                    'vendor_settings' => [],
                    'category_id'     => 'category_4',
                    'total_price'     => 300,
                ],
                [
                    'is_applicable'             => true,
                    'calculator_source'         => FlatCommissionCalculator::SOURCE,
                    'strategy_source'           => GlobalCommissionSourceStrategy::SOURCE,
                    'admin_commission'          => 5,
                    'per_item_admin_commission' => 5,
                    'vendor_earning'            => 295,
                ],
            ],
            [
                [
                    'data_set'        => 5,
                    'product_setting' => '',
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => FlatCommissionCalculator::SOURCE,
                        "admin_percentage"                 => "10",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [],
                    ],
                    'vendor_settings' => [
                        'percentage'           => 10,
                        'type'                 => PercentageCommissionCalculator::SOURCE,
                        'flat'                 => 5,
                    ],
                    'category_id'     => 'category_4',
                    'total_price'     => 300,
                ],
                [
                    'is_applicable'             => true,
                    'calculator_source'         => PercentageCommissionCalculator::SOURCE,
                    'strategy_source'           => VendorCommissionSourceStrategy::SOURCE,
                    'admin_commission'          => 30,
                    'per_item_admin_commission' => 30,
                    'vendor_earning'            => 270,
                ],
            ],
            [
                [
                    'data_set'        => 6,
                    'product_setting' => '',
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => FlatCommissionCalculator::SOURCE,
                        "admin_percentage"                 => "10",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [],
                    ],
                    'vendor_settings' => [
                        'percentage'           => 10,
                        'type'                 => PercentageCommissionCalculator::SOURCE,
                        'flat'                 => 5,
                    ],
                    'category_id'     => 'category_4',
                    'total_price'     => 300,
                ],
                [
                    'is_applicable'             => true,
                    'calculator_source'         => PercentageCommissionCalculator::SOURCE,
                    'strategy_source'           => VendorCommissionSourceStrategy::SOURCE,
                    'admin_commission'          => 30,
                    'per_item_admin_commission' => 30,
                    'vendor_earning'            => 270,
                ],
            ],
            [
                [
                    'data_set'        => 7,
                    'product_setting' => '',
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => CombineCommissionCalculator::SOURCE,
                        "admin_percentage"                 => "10",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [],
                    ],
                    'vendor_settings' => [],
                    'category_id'     => 'category_4',
                    'total_price'     => 300,
                ],
                [
                    'is_applicable'             => true,
                    'calculator_source'         => CombineCommissionCalculator::SOURCE,
                    'strategy_source'           => GlobalCommissionSourceStrategy::SOURCE,
                    'admin_commission'          => 35,
                    'per_item_admin_commission' => 35,
                    'vendor_earning'            => 265,
                ],
            ],
            [
                [
                    'data_set'        => 8,
                    'product_setting' => [
                        'percentage' => 5,
                        'type'       => CombineCommissionCalculator::SOURCE,
                        'flat'       => 5,
                    ],
                    'global_setting'  => [
                        "shipping_fee_recipient"           => "admin",
                        "tax_fee_recipient"                => "admin",
                        "shipping_tax_fee_recipient"       => "admin",
                        "new_seller_enable_selling"        => "on",
                        "commission_type"                  => CombineCommissionCalculator::SOURCE,
                        "admin_percentage"                 => "10",
                        "additional_fee"                   => "5",
                        "order_status_change"              => "on",
                        "product_category_style"           => 'single',
                        "commission_category_based_values" => [],
                    ],
                    'vendor_settings' => [],
                    'category_id'     => 'category_4',
                    'total_price'     => 300,
                ],
                [
                    'is_applicable'             => true,
                    'calculator_source'         => CombineCommissionCalculator::SOURCE,
                    'strategy_source'           => ProductCommissionSourceStrategy::SOURCE,
                    'admin_commission'          => 20,
                    'per_item_admin_commission' => 20,
                    'vendor_earning'            => 280,
                ],
            ],
        ];
    }

    public function replace_categoty_id( $items ) {
        $data = [];

        foreach ( $items as $key => $values ) {
            $index = str_replace( 'category_', '', $key );

            $data[ $this->category_ids[ $index ] ] = $values;
        }

        return $data;
    }

    /**
     * @dataProvider product_settings_data_provider
     * @test
     * @return void
     */
    public function test_get_earning_by_product_method( $settings, $expected ) {
        if ( isset( $settings['vendor_settings']['category_commissions'] ) ) {
            $settings['global_setting']['commission_category_based_values']['items'] = $this->replace_categoty_id( $settings['global_setting']['commission_category_based_values']['items'] );
        }

        if ( isset( $settings['vendor_settings']['category_commissions'] ) ) {
            $settings['vendor_settings']['category_commissions']['items'] = $this->replace_categoty_id( $settings['vendor_settings']['category_commissions']['items'] );
        }

        $vendor = $this->factory()->user->create_and_get(
            [
                'role' => 'seller',
            ]
        );
        $vendor = dokan()->vendor->get( $vendor->ID );


        $customer = $this->factory()->user->create_and_get(
            [
                'role' => 'customer',
            ]
        );

        wp_set_current_user( $customer->ID );

        $index      = str_replace( 'category_', '', $settings['category_id'] );
        $chosen_cat = $this->category_ids[ $index ];

        $product = WC_Helper_Product::create_simple_product(
            true,
            [
                'regular_price' => $settings['total_price'],
                'categories'    => $this->category_ids,
            ]
        );
        $product->update_meta_data( 'chosen_product_cat', [ $chosen_cat ] );
        $product->save_meta_data();
        $product->save();

        dokan()->product->save_commission_settings( $product->get_id(), $settings['product_setting'] );

        $product = dokan()->product->get( $product->get_id() );

        $vendor->save_commission_settings( $settings['vendor_settings'] );
        update_option( 'dokan_selling', $settings['global_setting'] );

        dokan_override_product_author( $product, $vendor->get_id() );

        $vendor_earning   = dokan()->commission->get_earning_by_product( $product, 'seller' );
        $admin_commission = dokan()->commission->get_earning_by_product( $product, 'admin' );

        $this->assertEquals( $expected['admin_commission'], $admin_commission );
        $this->assertEquals( $expected['vendor_earning'], $vendor_earning );
    }

    /**
     * @test
     * @dataProvider product_settings_data_provider
     * @return void
     */
    public function test_get_earning_by_order_method( $settings, $expected ) {
        if ( isset( $settings['vendor_settings']['category_commissions'] ) ) {
            $settings['global_setting']['commission_category_based_values']['items'] = $this->replace_categoty_id( $settings['global_setting']['commission_category_based_values']['items'] );
        }

        if ( isset( $settings['vendor_settings']['category_commissions'] ) ) {
            $settings['vendor_settings']['category_commissions']['items'] = $this->replace_categoty_id( $settings['vendor_settings']['category_commissions']['items'] );
        }

        $vendor = $this->factory()->user->create_and_get(
            [
                'role' => 'seller',
            ]
        );
        $vendor = dokan()->vendor->get( $vendor->ID );


        $customer = $this->factory()->user->create_and_get(
            [
                'role' => 'customer',
            ]
        );

        wp_set_current_user( $customer->ID );

        $index      = str_replace( 'category_', '', $settings['category_id'] );
        $chosen_cat = $this->category_ids[ $index ];

        $product = WC_Helper_Product::create_simple_product(
            true,
            [
                'regular_price' => $settings['total_price'],
                'categories'    => $this->category_ids,
            ]
        );
        $product->update_meta_data( 'chosen_product_cat', [ $chosen_cat ] );
        $product->save_meta_data();
        $product->save();

        dokan()->product->save_commission_settings( $product->get_id(), $settings['product_setting'] );

        $product = dokan()->product->get( $product->get_id() );

        $vendor->save_commission_settings( $settings['vendor_settings'] );
        update_option( 'dokan_selling', $settings['global_setting'] );

        dokan_override_product_author( $product, $vendor->get_id() );

        $order = WC_Helper_Order::create_order( $customer->ID, $product );
        $order->update_meta_data( '_dokan_vendor_id', $vendor->get_id() );
        $order->save();

        $order = dokan()->order->get( $order->get_id() );

        $vendor_earning   = dokan()->commission->get_earning_by_order( $order, 'seller' );
        $admin_commission = dokan()->commission->get_earning_by_order( $order, 'admin' );

        $shipping_cost = wc_format_decimal( floatval( $order->get_shipping_total() ) ) - $order->get_total_shipping_refunded();
        'admin' === dokan()->fees->get_shipping_fee_recipient( $order ) ? $expected['admin_commission'] += $shipping_cost : $expected['vendor_earning'] += $shipping_cost;

        $tax_cost = ( ( $order->get_total_tax() - $order->get_total_tax_refunded() ) - ( $order->get_shipping_tax() - dokan()->fees->get_total_shipping_tax_refunded( $order ) ) );
        'admin' === dokan()->fees->get_tax_fee_recipient( $order->get_id() ) ? $expected['admin_commission'] += $tax_cost : $expected['vendor_earning'] += $tax_cost;

        $shipping_tax_cost = ( $order->get_shipping_tax() - dokan()->fees->get_total_shipping_tax_refunded( $order ) );
        'admin' === dokan()->fees->get_shipping_tax_fee_recipient( $order ) ? $expected['admin_commission'] += $shipping_tax_cost : $expected['vendor_earning'] += $shipping_tax_cost;

        $this->assertEquals( $expected['admin_commission'], $admin_commission );
        $this->assertEquals( $expected['vendor_earning'], $vendor_earning );
    }

    /**
     * @test
     * @dataProvider product_settings_data_provider
     * @return void
     */
    public function test_get_commission_method( $settings, $expected ) {
        if ( isset( $settings['vendor_settings']['category_commissions'] ) ) {
            $settings['global_setting']['commission_category_based_values']['items'] = $this->replace_categoty_id( $settings['global_setting']['commission_category_based_values']['items'] );
        }

        if ( isset( $settings['vendor_settings']['category_commissions'] ) ) {
            $settings['vendor_settings']['category_commissions']['items'] = $this->replace_categoty_id( $settings['vendor_settings']['category_commissions']['items'] );
        }

        $vendor = $this->factory()->user->create_and_get(
            [
                'role' => 'seller',
            ]
        );
        $vendor = dokan()->vendor->get( $vendor->ID );


        $customer = $this->factory()->user->create_and_get(
            [
                'role' => 'customer',
            ]
        );

        wp_set_current_user( $customer->ID );

        $index      = str_replace( 'category_', '', $settings['category_id'] );
        $chosen_cat = $this->category_ids[ $index ];

        $product = WC_Helper_Product::create_simple_product(
            true,
            [
                'regular_price' => $settings['total_price'],
                'categories'    => $this->category_ids,
            ]
        );
        $product->update_meta_data( 'chosen_product_cat', [ $chosen_cat ] );
        $product->save_meta_data();
        $product->save();


        $product = dokan()->product->get( $product->get_id() );

        dokan()->product->save_commission_settings( $product->get_id(), $settings['product_setting'] );
        $vendor->save_commission_settings( $settings['vendor_settings'] );
        update_option( 'dokan_selling', $settings['global_setting'] );

        dokan_override_product_author( $product, $vendor->get_id() );

        $order = WC_Helper_Order::create_order(
            $customer->ID,
            $product
        );

        $items = $order->get_items();
        $item  = reset( $items );

        $commission = dokan()->commission->get_commission(
            [
                'order_item_id'  => $item->get_id(),
                'total_amount'   => $item->get_total(),
                'total_quantity' => $item->get_quantity(),
                'product_id'     => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
                'vendor_id'      => $vendor->get_id(),
                'category_id'    => $chosen_cat,
            ],
            true
        );

        $this->assertEquals( $expected['calculator_source'], $commission->get_type() );
        $this->assertEquals( $expected['strategy_source'], $commission->get_source() );
        $this->assertEquals( $expected['admin_commission'], $commission->get_admin_commission() );
        $this->assertEquals( $expected['vendor_earning'], $commission->get_vendor_earning() );


        // Resetting the settings
        dokan()->product->save_commission_settings( $product->get_id(), '' );
        $vendor->save_commission_settings( '' );
        update_option( 'dokan_selling', [] );

        $saved_commission = dokan()->commission->get_commission(
            [
                'order_item_id'  => $item->get_id(),
                'total_amount'   => $item->get_total(),
                'total_quantity' => $item->get_quantity(),
                'product_id'     => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
                'vendor_id'      => $vendor->get_id(),
                'category_id'    => $chosen_cat,
            ],
        );

        $this->assertEquals( OrderItemCommissionSourceStrategy::SOURCE, $saved_commission->get_source() );
        $this->assertEquals( $expected['calculator_source'], $saved_commission->get_type() );
        $this->assertEquals( $expected['admin_commission'], $saved_commission->get_admin_commission() );
        $this->assertEquals( $expected['vendor_earning'], $saved_commission->get_vendor_earning() );

        $this->assertEquals( $commission->get_admin_commission(), $saved_commission->get_admin_commission() );
        $this->assertEquals( $commission->get_vendor_earning(), $saved_commission->get_vendor_earning() );


        $saved_commission_cross_check = dokan()->commission->get_commission(
            [
                'order_item_id'  => $item->get_id(),
                'total_amount'   => $item->get_total(),
                'total_quantity' => $item->get_quantity(),
                'product_id'     => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
                'vendor_id'      => $vendor->get_id(),
                'category_id'    => $chosen_cat,
            ],
        );

        $this->assertEquals( OrderItemCommissionSourceStrategy::SOURCE, $saved_commission_cross_check->get_source() );
        $this->assertEquals( $expected['calculator_source'], $saved_commission_cross_check->get_type() );
        $this->assertEquals( $expected['admin_commission'], $saved_commission_cross_check->get_admin_commission() );
        $this->assertEquals( $expected['vendor_earning'], $saved_commission_cross_check->get_vendor_earning() );

        $this->assertEquals( $commission->get_admin_commission(), $saved_commission_cross_check->get_admin_commission() );
        $this->assertEquals( $commission->get_vendor_earning(), $saved_commission_cross_check->get_vendor_earning() );
    }
}
