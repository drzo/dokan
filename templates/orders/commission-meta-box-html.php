<?php
/**
 * Dokan Template
 *
 * Dokan Dashboard Commission Meta-box Content Template
 *
 * @var WC_Order $order
 * @var object $data
 * @var float $total_commission
 *
 * @since DOKAN_LITE
 *
 * @package dokan
 */
?>

<div id="woocommerce-order-items" class="postbox" style='border: none'>
    <div class="postbox-header">
        <div class="handle-actions hide-if-no-js">
            <button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="woocommerce-order-items-handle-order-higher-description">
                <span class="order-higher-indicator" aria-hidden="true"></span>
            </button>
            <button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="woocommerce-order-items-handle-order-lower-description">
                <span class="screen-reader-text"></span>
                <span class="order-lower-indicator" aria-hidden="true"></span>
            </button>
            <button type="button" class="handlediv" aria-expanded="true">
                <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
        </div>
    </div>
    <div class="inside">
        <div class="woocommerce_order_items_wrapper wc-order-items-editable">
            <table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
                <thead>
                <tr>
                    <th colspan="2">Item</th>
                    <th><?php esc_html_e( 'Type', 'dokan-lite' ); ?></th>
                    <th><?php esc_html_e( 'Rate', 'dokan-lite' ); ?></th>
                    <th><?php esc_html_e( 'Qty', 'dokan-lite' ); ?></th>
                    <th><?php esc_html_e( 'Commission', 'dokan-lite' ); ?></th>
                </tr>
                </thead>

                <tbody id="order_line_items">
                <?php foreach ( $order->get_items() as $item_id => $item ) : ?>
                    <?php
                    $product      = $item->get_product();
                    $product_link = $product ? admin_url( 'post.php?post=' . $item->get_product_id() . '&action=edit' ) : '';
                    $thumbnail    = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
                    $row_class    = apply_filters( 'woocommerce_admin_html_order_item_class', ! empty( $class ) ? $class : '', $item, $order );
                    $commission   = 0;

                    $commission_type_html = $item->get_meta( '_dokan_commission_type' ) && isset( dokan_commission_types()[ $item->get_meta( '_dokan_commission_type' ) ] ) ? dokan_commission_types()[ $item->get_meta( '_dokan_commission_type' ) ] : '';
                    ?>
                    <tr class="item  <?php echo esc_attr( $row_class ); ?>" data-order_item_id="<?php echo $item->get_id(); ?>">
                        <td class="thumb">
                            <?php echo '<div class="wc-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
                        </td>
                        <td class="name">
                            <?php
                            echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wc-order-item-name">' . wp_kses_post( $item->get_name() ) . '</a>' : '<div class="wc-order-item-name">' . wp_kses_post( $item->get_name() ) . '</div>';

                            if ( $product && $product->get_sku() ) {
                                echo '<div class="wc-order-item-sku"><strong>' . esc_html__( 'SKU:', 'dokan-lite' ) . '</strong> ' . esc_html( $product->get_sku() ) . '</div>';
                            }

                            if ( $item->get_variation_id() ) {
                                echo '<div class="wc-order-item-variation"><strong>' . esc_html__( 'Variation ID:', 'dokan-lite' ) . '</strong> ';
                                if ( 'product_variation' === get_post_type( $item->get_variation_id() ) ) {
                                    echo esc_html( $item->get_variation_id() );
                                } else {
                                    /* translators: %s: variation id */
                                    printf( esc_html__( '%s (No longer exists)', 'dokan-lite' ), esc_html( $item->get_variation_id() ) );
                                }
                                echo '</div>';
                            }
                            ?>
                        </td>


                        <td width="1%">
                            <div class="view">
                                <bdi><?php echo esc_html( $commission_type_html ); ?></bdi>
                            </div>
                        </td>
                        <td width="1%">
                            <div class="view">
                                <?php if ( 'flat' === $item->get_meta( '_dokan_commission_type' ) ) : ?>
                                    <?php echo esc_html( $item->get_meta( '_dokan_commission_rate' ) . get_woocommerce_currency_symbol() ); ?>
                                <?php elseif ( 'percentage' === $item->get_meta( '_dokan_commission_type' ) ) : ?>
                                    <?php echo esc_html( $item->get_meta( '_dokan_commission_rate' ) . '%' ); ?>
                                <?php elseif ( 'combine' === $item->get_meta( '_dokan_commission_type' ) ) : ?>
                                    <?php echo esc_html( $item->get_meta( '_dokan_commission_rate' ) . '%' ); ?>&nbsp;+&nbsp;<?php echo esc_html( $item->get_meta( '_dokan_additional_fee' ) . get_woocommerce_currency_symbol() ); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="quantity" width="1%">
                            <div class="view">
                                <?php
                                echo '<small class="times">&times;</small> ' . esc_html( $item->get_quantity() );

                                $refunded_qty = -1 * $order->get_qty_refunded_for_item( $item_id );

                                if ( $refunded_qty ) {
                                    echo '<small class="refunded">' . esc_html( $refunded_qty * -1 ) . '</small>';
                                }
                                ?>
                            </div>
                        </td>
                        <td width="1%">
                            <div class="view">
                                <?php
                                $amount = $item->get_total();
                                $refunded_amount = -1 * $order->get_total_refunded_for_item( $item_id );

                                $original_commission = 0;
                                if ( 'flat' === $item->get_meta( '_dokan_commission_type' ) ) {
                                    $original_commission = (float) $item->get_meta( '_dokan_commission_rate' ) * (int) $item->get_quantity();
                                } elseif ( 'percentage' === $item->get_meta( '_dokan_commission_type' ) ) {
                                    $original_commission = ( (float) $item->get_meta( '_dokan_commission_rate' ) * (float) $amount ) / 100;
                                } elseif ( 'combine' === $item->get_meta( '_dokan_commission_type' ) ) {
                                    $original_commission = ( ( (float) $item->get_meta( '_dokan_commission_rate' ) * (float) $amount ) / 100 ) + (float) $item->get_meta( '_dokan_additional_fee' );
                                }

                                if ( $refunded_amount ) {
                                    $commission_refunded = ( $order->get_total_refunded_for_item( $item_id ) / $amount ) * $original_commission;
                                }

                                echo '<bdi>' . wc_price( $original_commission, array( 'currency' => $order->get_currency() ) ) . '</bdi>';

                                if ( $refunded_amount ) {
                                    echo '<small class="refunded">' . wc_price( $commission_refunded, array( 'currency' => $order->get_currency() ) ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
            <table class="wc-order-totals">
                <tbody>
                <tr>
                    <td class="label"><?php esc_html_e( 'Order total:', 'dokan-lite' ); ?></td>
                    <td width="1%"></td>
                    <td class="total">
                        <?php echo wc_price( $data->order_total, array( 'currency' => $order->get_currency() ) ); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label"><?php esc_html_e( 'Vendor earning:', 'dokan-lite' ); ?></td>
                    <td width="1%"></td>
                    <td class="total">
                        <?php echo wc_price( $data->net_amount, array( 'currency' => $order->get_currency() ) ); ?>
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="clear"></div>


            <table class="wc-order-totals" style="border-top: 1px solid #999; border-bottom: none; margin-top:12px; padding-top:12px">
                <tbody>
                <tr>
                    <td class="label label-highlight"><?php esc_html_e( 'Total commission:', 'dokan-lite' ); ?></td>
                    <td width="1%"></td>
                    <td class="total">
                        <?php echo wc_price( $total_commission, array( 'currency' => $order->get_currency() ) ); ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
