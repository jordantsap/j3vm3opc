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
$SHIPTO_SELECT = $this->getShipToSelectData_byPV();

?>

<div id="bypv_cart_shipping_address_select" class="cart_block">
	<?php $this->printHeader_byPV(2, 'PLG_SYSTEM_OPC_FOR_VM_BYPV_SELECT_SHIPTO_TITLE'); ?>

	<fieldset class="clean">
	
		<?php if ($CART->IS_PHASE_CHECKOUT) { ?>
			
			<ul class="clean">
				<?php foreach ($SHIPTO_SELECT->ADDRESSES as $ADDRESS_ID => $ADDRESS) { ?>
					<li>
						<input type="radio"
							id="shipto_<?php echo $ADDRESS_ID; ?>"
							name="shipto" value="<?php echo $ADDRESS_ID; ?>"
							<?php if ($ADDRESS_ID == $SHIPTO_SELECT->SELECTED_ADDRESS) echo 'checked="checked"' ?>
						/>
						
						<label for="shipto_<?php echo $ADDRESS_ID; ?>">
							<?php echo JText::_($ADDRESS->NAME); ?>
						</label>
						
						<?php if ($SHIPTO_SELECT->PLGCFG_ALLOW_DELETE_SHIPPING_ADDRESS && !isset(VirtueMartCart_byPV::$SHIPPING_TO_TYPES[$ADDRESS_ID])) { ?>
							<input type="button" class="bypv_remove_address_button image_button" title="Delete Address" value=" " />
						<?php } ?>
					</li>
				<?php } ?>
			</ul>
				
		<?php } elseif ($SHIPTO_SELECT->SELECTED_ADDRESS == VirtueMartCart_byPV::ST_SAME_AS_BILL_TO) { ?>
		
			<p><?php echo JText::_($SHIPTO_SELECT->ADDRESSES[$SHIPTO_SELECT->SELECTED_ADDRESS]->NAME); ?></p>
			
		<?php } ?>
		
	</fieldset>
</div>