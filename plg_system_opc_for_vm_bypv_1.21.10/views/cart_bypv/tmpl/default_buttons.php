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

$BUTTONS = $this->getButtonsData_byPV();

?>

<div id="cart_buttons" class="cart_block">
	<fieldset class="clean">
		<?php if ($BUTTONS->SHOW_CHECKOUT_BUTTON) { ?>
			<input type="submit" class="text_button checkout_button" name="bypv_submit_checkout" value="<?php echo JText::_('COM_VIRTUEMART_CHECKOUT_TITLE'); ?>" />
		<?php } ?>
		
		<?php if ($BUTTONS->SHOW_BACK_TO_CHECKOUT_BUTTON) { ?>
			<input type="button" class="text_button" name="bypv_submit_back_to_checkout" value="<?php echo JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_BACK_TO_CHECKOUT_BUTTON'); ?>" />
		<?php } ?>
			
		<?php if ($BUTTONS->SHOW_CONFIRM_BUTTON) { ?>
			<input type="submit" class="text_button checkout_button" name="bypv_submit_confirm" value="<?php echo JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_CONFIRM_BUTTON'); ?>" />
		<?php } ?>
	</fieldset>
</div>
