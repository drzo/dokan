<?php

namespace WeDevs\Dokan\Upgrade\Upgrades;

use WeDevs\Dokan\Abstracts\DokanUpgrader;

class V_3_9_10 extends DokanUpgrader {

    public static function update_commission_type() {
        /**
         * TODO: commission-restructure gets all category commission and saves into global category based commisssion.
         * TODO: commission-restructure update all venodr saved commission type to fixed.
         */
        $options         = get_option( 'dokan_selling', [] );
        $commission_type = isset( $options['dokan_commission_type'] ) ? $options['dokan_commission_type'] : 'fixed';

        if ( in_array( $commission_type, array_keys( dokan()->commission->get_legacy_commission_types() ), true ) ) {
            $options['dokan_commission_type'] = 'fixed';
            update_option( 'dokan_selling', $options );
        }
    }
}
