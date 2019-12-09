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

?>

<form method="post" novalidate="novalidate" action="<?php echo $CART->DEFAULT_URL; ?>">
	<div id="bypv_change_shopper" class="cart_block">
		<?php $this->printHeader_byPV(2, 'COM_VIRTUEMART_CART_CHANGE_SHOPPERGROUP'); ?>
	
		<fieldset class="clean">
		
			<?php if (!empty($CART->CHANGE_SHOPPER_GROUP_LIST)) echo $CART->CHANGE_SHOPPER_GROUP_LIST; ?>
			<input type="submit" name="changeShopperGroup" title="<?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?>"
				value="<?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?>" class="text_button" />
			
			<input type="hidden" name="task" value="changeShopperGroup" />
			<input type="hidden" name="return" value="<?php echo base64_encode('index.php?option=com_virtuemart&view=cart'); ?>" />
			<?php echo JHtml::_('form.token'); ?>

			<?php if (JFactory::getSession()->get('tempShopperGroups', FALSE, 'vm')) { ?>
				<input type="reset" title="<?php echo vmText::_('COM_VIRTUEMART_RESET'); ?>"
					value="<?php echo vmText::_('COM_VIRTUEMART_RESET'); ?>" class="text_button"
					onclick="window.location.href='<?php echo JRoute::_('index.php?option=com_virtuemart&view=cart&task=resetShopperGroup'); ?>'" />
			<?php } ?>
	
		</fieldset>
	</div>
</form>
