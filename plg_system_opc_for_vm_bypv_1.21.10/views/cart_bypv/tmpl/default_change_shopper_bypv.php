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

$CART = $this->getCartData_byPV();

if (isset($CART->CURRENT_ADMIN) && $CART->CURRENT_ADMIN != $CART->CURRENT_USER)
{
	$ACTIVE_ADMIN_TEXT = ' <strong>(' . JText::_('COM_VIRTUEMART_CART_ACTIVE_ADMIN') . ' ' . JFactory::getUser($CART->CURRENT_ADMIN)->name . ')</strong>';
}
else $ACTIVE_ADMIN_TEXT = NULL;;

?>

<form method="post" novalidate="novalidate" action="<?php echo $CART->DEFAULT_URL; ?>">
	<div id="bypv_change_shopper" class="cart_block">
		<?php $this->printHeader_byPV(2, 'COM_VIRTUEMART_CART_CHANGE_SHOPPER', $ACTIVE_ADMIN_TEXT); ?>
	
		<fieldset class="clean">
		
			<?php if (VM_VERSION === 3) { ?>
				<input type="text" name="usersearch" size="20" maxlength="50" />
				<input type="submit" name="searchShopper" title="<?php echo vmText::_('COM_VIRTUEMART_SEARCH'); ?>" value="<?php echo vmText::_('COM_VIRTUEMART_SEARCH'); ?>" class="text_button" />
			<?php } ?>
	
			<?php echo JHtml::_('select.genericlist', $CART->CHANGE_SHOPPER_LIST, 'userID', 'class="vm-chzn-select" style="width: 200px"', 'id', 'displayedName', $CART->CURRENT_USER, 'userIDcart'); ?>
			<input type="submit" name="changeShopper" title="<?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?>" value="<?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?>" class="text_button" />
			
			<input type="hidden" name="task" value="changeShopper" />
			<input type="hidden" name="return" value="<?php echo base64_encode('index.php?option=com_virtuemart&view=cart'); ?>" />
			<?php echo JHtml::_('form.token'); ?>
	
		</fieldset>
	</div>
</form>