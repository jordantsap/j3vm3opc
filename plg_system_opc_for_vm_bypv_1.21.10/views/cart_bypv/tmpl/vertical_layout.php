<?php defined ('_JEXEC') or die('Restricted access');

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

?>

<?php // Product List ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_PRODUCT_LIST); ?>

<?php // External Modules ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_EXTERNAL_MODULES); ?>

<?php // Coupon Code ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_COUPON_CODE); ?>

<?php // Shipment ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_SHIPMENTS); ?>

<?php // Payment ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_PAYMENTS); ?>

<?php // Advertisements ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_ADVERTISEMENTS); ?>

<?php // Customer Type Select ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_CUSTOMER_TYPE_SELECT); ?>

<div id="cart_customer">
	<fieldset class="clean">
		<?php // Login ?>

		<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_LOGIN); ?>

		<?php // Billing Address ?>

		<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_BILLING_ADDRESS); ?>

		<?php // Shipping Address ?>

		<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_SHIPPING_ADDRESS); ?>
	</fieldset>
</div>

<?php // Cart Fields (VM3) ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_CART_FIELDS); ?>

<?php // Customer Comment (VM2) ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_COMMENT); ?>

<?php // Terms Of Service (VM2) ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_TOS); ?>

<?php // Order Summary ?>

<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_ORDER_SUMMARY); ?>

<?php // Checkout and Confirm Button ?>

<?php echo $this->loadTemplate('buttons'); ?>
