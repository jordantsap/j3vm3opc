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

/*** TEMPLATE VARIABLES ***/

$CART = $this->getCartData_byPV();

JHtml::_('behavior.keepalive');

?>

<div id="bypv_cart" class="<?php echo ($CART->IS_PHASE_CHECKOUT ? 'checkout' : 'confirm'); ?>">
	<div class="cart">
	
		<div class="cart_toolbar">
		
			<?php // Empty Cart Button ?>
		
			<?php if ($CART->PLGCFG_ALLOW_EMPTY_CART && !$CART->IS_EMPTY && $CART->IS_PHASE_CHECKOUT) { ?>
			
				<input type="button" class="bypv_empty_cart_button text_button" value="<?php echo JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_EMPTY_CART_BUTTON_LABEL'); ?>" />
				
			<?php } ?>
		
			<?php // Continue Link ?>
		
			<?php if ($CART->IS_CONTINUE_LINK && $CART->IS_PHASE_CHECKOUT) { ?>
			
				<input type="button" class="bypv_continue_link_button text_button"
					value="<?php echo JText::_('COM_VIRTUEMART_CONTINUE_SHOPPING'); ?>"
					onclick="location.href = '<?php echo $CART->CONTINUE_LINK; ?>';"
				/>
				
			<?php } ?>
		
		</div>

		<?php $this->printHeader_byPV(1, 'COM_VIRTUEMART_CART_TITLE'); ?>

		<?php // Change Shopper Form (must be enabled in VM Configuration and it's showed for logged admin only) ?>

		<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_CHANGE_SHOPPER); ?>

		<?php echo $this->loadFormTemplate_byPV(VirtueMartViewCart_byPV::TPL_CHANGE_SHOPPER_GROUP); ?>
			
		<?php if ($CART->IS_EMPTY) { ?>
	
			<?php // Empty Cart ?>
		
			<p class="empty"><?php echo JText::_('COM_VIRTUEMART_EMPTY_CART'); ?></p>
			
		<?php } else { ?>

			<?php // Layout ?>
		
			<form method="post" id="bypv_cart_form" novalidate="novalidate" action="<?php echo $CART->CHECKOUT_URL; ?>" autocomplete="off">
		
				<?php echo $this->loadTemplate('layout'); ?>

				<?php // Hidden Form Inputs ?>
				
				<input type='hidden' name='order_language' value='<?php echo $CART->ORDER_LANGUAGE; ?>' />
				<input type='hidden' name='task' value='<?php echo $CART->CHECKOUT_TASK; ?>' />
				<input type='hidden' name='option' value='com_virtuemart' />
				<input type='hidden' name='view' value='cart' />
				
				<?php // Form Checksum (must be here - in the end of main form) ?>
				
				<input type='hidden' name='bypv_form_checksum' value='<?php echo $this->getFormChecksum_byPV(); ?>' />
				<?php echo JHtml::_('form.token' ); ?>
				
			</form>
		
		<?php } ?>
	</div>
</div>