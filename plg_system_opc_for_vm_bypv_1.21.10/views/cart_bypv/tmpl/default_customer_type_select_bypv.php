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
$CUSTOMER = $this->getCustomerData_byPV();

?>

<div id="bypv_cart_customer_type_select" class="cart_block">
	<?php $this->printHeader_byPV(2, 'PLG_SYSTEM_OPC_FOR_VM_BYPV_SELECT_CUSTOMER_TYPE_TITLE'); ?>

	<fieldset class="clean">
		<ul class="clean">
			<?php foreach ($CUSTOMER->TYPES as $TYPE_ID => $TYPE) { ?>
				<li>
					<input
						type="radio"
						id="bypv_customer_type_<?php echo $TYPE_ID; ?>"
						name="bypv_customer_type"
						value="<?php echo $TYPE_ID; ?>"
						<?php if ($TYPE_ID == $CUSTOMER->SELECTED_TYPE && $TYPE->ALLOWED === TRUE) echo 'checked="checked"'; ?>
						<?php if ($TYPE->ALLOWED !== TRUE) echo 'disabled="disabled"'; ?>
					/>
	
					<label for="bypv_customer_type_<?php echo $TYPE_ID; ?>" title="<?php echo JText::_($TYPE->DESCRIPTION); ?>">
						<?php echo JText::_($TYPE->NAME); ?>
					</label>
				</li>
				
			<?php } ?>
		</ul>
	</fieldset>
</div>
