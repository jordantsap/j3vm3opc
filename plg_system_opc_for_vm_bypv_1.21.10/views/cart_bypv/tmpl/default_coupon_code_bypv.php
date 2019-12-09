<?php defined('_JEXEC') or die('Restricted access');

/**
 * Plugin: One Page Checkout for VirtueMart byPV
 * Copyright (C) 2014 byPV.org <info@bypv.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*** TEMPLATE VARIABLES ***/

$COUPON_CODE = $this->getCouponCodeData_byPV();

?>

<div id="bypv_cart_coupon_code" class="cart_block">
	<?php $this->printHeader_byPV(2, 'COM_VIRTUEMART_COUPON_DISCOUNT'); ?>

	<fieldset class="clean">
	    <input type="text" name="bypv_coupon_code" size="30" maxlength="50" placeholder="<?php echo $COUPON_CODE->PLACEHOLDER_TEXT; ?>" autocomplete="<?php echo $COUPON_CODE->INPUT_AUTOCOMPLETE; ?>" />
	    <input class="bypv_coupon_code_button text_button" type="button" value="<?php echo JText::_('COM_VIRTUEMART_SAVE'); ?>" />
	</fieldset>
</div>