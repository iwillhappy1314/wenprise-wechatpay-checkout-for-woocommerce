/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import {Content, ariaLabel, Label} from './base';

const settings = getSetting( 'wprs-wc-wechatpay_data', {} );
const label = ariaLabel({ title: settings.title });

/**
 * Paystack payment method config object.
 */
const Wenprise_Wechatpay_Gateway = {
  name: 'wprs-wc-wechatpay',
  label: <Label logoUrls={ settings.logo_urls } title={ label } />,
  content: <Content description={ settings.description } />,
  edit: <Content description={ settings.description } />,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
};

registerPaymentMethod( Wenprise_Wechatpay_Gateway );