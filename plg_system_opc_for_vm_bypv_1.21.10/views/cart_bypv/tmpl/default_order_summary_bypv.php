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

// Backward compatibility for some of language constants

$lang = JFactory::getLanguage();

$pluginConstants = array(
		'PLG_SYSTEM_OPC_FOR_VM_BYPV_PRODUCT_LIST_PRICE_EXCL_TAX' => 'COM_VIRTUEMART_CART_PRICE',
		'PLG_SYSTEM_OPC_FOR_VM_BYPV_PRODUCT_LIST_TOTAL_EXCL_TAX' => 'COM_VIRTUEMART_CART_TOTAL',
);

foreach ($pluginConstants as $pluginKey => $vmKey)
{
	// Language Constant
	$lgName = 'LC_' . $pluginKey;

	if ($lang->hasKey($pluginKey)) $$lgName = $pluginKey;
	else $$lgName = $vmKey;
}

/*** TEMPLATE VARIABLES ***/

$CART = $this->getCartData_byPV();
$PRODUCT_LIST = $this->getProductListData_byPV();

?>

<div id="bypv_cart_order_summary" class="cart_block">

<?php $this->printHeader_byPV(2, 'PLG_SYSTEM_OPC_FOR_VM_BYPV_ORDER_SUMMARY_TITLE'); ?>

<fieldset class="clean">

<table id="cart_summary_table" class="summary_table clean">

	<?php // Table Header ?>

	<thead>
		<tr>
			<th class="order_content"><?php echo JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_ORDER_SUMMARY_ORDER_CONTENT'); ?></th>
			
			<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
				<?php if ($COL->ID == 'DISCOUNT') { ?>
					<th class="discount"><?php echo JText::_('COM_VIRTUEMART_CART_SUBTOTAL_DISCOUNT_AMOUNT'); ?></th>
				<?php } ?>
				
				<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
					<th class="total_excl_tax"><?php echo JText::_($LC_PLG_SYSTEM_OPC_FOR_VM_BYPV_PRODUCT_LIST_TOTAL_EXCL_TAX); ?></th>
				<?php } ?>
				
				<?php if ($COL->ID == 'TAX') { ?>
					<th class="tax"><?php echo JText::_('COM_VIRTUEMART_CART_SUBTOTAL_TAX_AMOUNT'); ?></th>
				<?php } ?>
				
				<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
					<th class="total_incl_tax">
						<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX'])) { ?>
							<span class="total_excl_tax responsive"><?php echo JText::_('COM_VIRTUEMART_CART_TOTAL'); ?><hr /></span>
						<?php } ?>
						
						<?php echo JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_PRODUCT_LIST_TOTAL_INCL_TAX'); ?>
					</th>
				<?php } ?>
			<?php } ?>
		</tr>
	</thead>
	
	<tfoot>
	
		<?php // Subtotal ?>

		<tr class="subtotal">
			<td class="label">
				<?php echo JText::_('COM_VIRTUEMART_ORDER_PRINT_PRODUCT_PRICES_TOTAL'); ?>
			</td>

			<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
				<?php if ($COL->ID == 'DISCOUNT') { ?>
					<td class="discount"><?php echo $PRODUCT_LIST->SUBTOTAL->DISCOUNT; ?></td>
				<?php } ?>
				
				<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
					<td class="total_excl_tax"><?php echo $PRODUCT_LIST->SUBTOTAL->TOTAL_EXCL_TAX; ?></td>
				<?php } ?>
				
				<?php if ($COL->ID == 'TAX') { ?>
					<td class="tax"><?php echo $PRODUCT_LIST->SUBTOTAL->TAX; ?></td>
				<?php } ?>
				
				<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
					<td class="total_incl_tax">
						<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX']) && $PRODUCT_LIST->SUBTOTAL->TOTAL_EXCL_TAX != $PRODUCT_LIST->SUBTOTAL->TOTAL_INCL_TAX) { ?>
							<span class="total_excl_tax responsive"><?php echo $PRODUCT_LIST->SUBTOTAL->TOTAL_EXCL_TAX; ?></span>
						<?php } ?>
					
						<?php echo $PRODUCT_LIST->SUBTOTAL->TOTAL_INCL_TAX; ?>
					</td>
				<?php } ?>
			<?php } ?>
		</tr>
		
		<?php // Coupon Code ?>
		
		<?php if ($PRODUCT_LIST->COUPON_CODE) { ?>
			<tr class="coupon_code">
				<td class="label">
					<label><?php echo JText::_('PLG_SYSTEM_OPC_FOR_VM_BYPV_COUPON_CODE') . ': '; ?></label>
					<span class="name"><?php echo $PRODUCT_LIST->COUPON_CODE->NAME; ?></span>

					<?php if ($PRODUCT_LIST->COUPON_CODE->ENTERED) { ?>
						<input type="button" class="bypv_coupon_code_remove_button image_button" value=" " />
					<?php } ?>

					<?php if ($PRODUCT_LIST->COUPON_CODE->SHOW_COUPON_CODE_INPUT) { ?>
						<span class="input">
							<input type="text" name="bypv_coupon_code" size="15" maxlength="50" placeholder="<?php echo $PRODUCT_LIST->COUPON_CODE->PLACEHOLDER_TEXT; ?>" autocomplete="<?php echo $PRODUCT_LIST->COUPON_CODE->INPUT_AUTOCOMPLETE; ?>" />
							<input type="button" class="bypv_coupon_code_button text_button" value="<?php echo JText::_('COM_VIRTUEMART_SAVE'); ?>" />
						</span>
					<?php } ?>
				</td>
				
				<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
					<?php if ($COL->ID == 'DISCOUNT') { ?>
						<td class="discount"><?php echo $PRODUCT_LIST->COUPON_CODE->DISCOUNT; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
						<td class="total_excl_tax"><?php echo $PRODUCT_LIST->COUPON_CODE->TOTAL_EXCL_TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TAX') { ?>
						<td class="tax"><?php echo $PRODUCT_LIST->COUPON_CODE->TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
						<td class="total_incl_tax">
							<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX']) && $PRODUCT_LIST->COUPON_CODE->TOTAL_EXCL_TAX != $PRODUCT_LIST->COUPON_CODE->TOTAL_INCL_TAX) { ?>
								<span class="total_excl_tax responsive"><?php echo $PRODUCT_LIST->COUPON_CODE->TOTAL_EXCL_TAX; ?></span>
							<?php } ?>
						
							<?php echo $PRODUCT_LIST->COUPON_CODE->TOTAL_INCL_TAX; ?>
						</td>
					<?php } ?>
				<?php } ?>
			</tr>
		<?php } ?>
		
		<?php // Tax Rules ?>
		
		<?php foreach ($PRODUCT_LIST->TAX_RULES as $ROW_CLASS => $RULES) { ?>
			<?php // ROW_CLASS = db_tax_rule OR tax_rule OR da_tax_rule ?>
			
			<?php foreach ($RULES as $RULE) { ?>
				<tr class="<?php echo $ROW_CLASS; ?>">
					<td class="label">
						<?php echo $RULE->NAME; ?>
					</td>
			
					<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
						<?php if ($COL->ID == 'DISCOUNT') { ?>
							<td class="discount"><?php echo $RULE->DISCOUNT; ?></td>
						<?php } ?>
						
						<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
							<td class="total_excl_tax"><?php echo $RULE->TOTAL_EXCL_TAX; ?></td>
						<?php } ?>
						
						<?php if ($COL->ID == 'TAX') { ?>
							<td class="tax"><?php echo $RULE->TAX; ?></td>
						<?php } ?>
						
						<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
							<td class="total_incl_tax">
								<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX']) && $RULE->TOTAL_EXCL_TAX != $RULE->TOTAL_INCL_TAX) { ?>
									<span class="total_excl_tax responsive"><?php echo $RULE->TOTAL_EXCL_TAX; ?></span>
								<?php } ?>
								
								<?php echo $RULE->TOTAL_INCL_TAX; ?>
							</td>
						<?php } ?>
					<?php } ?>
				</tr>
			<?php } ?>

		<?php } ?>
		
		<?php // Shipment ?>
	
		<?php if ($PRODUCT_LIST->SHIPMENT) { ?>
			<tr class="shipment">
				<td class="label">
					<label><?php echo JText::_('COM_VIRTUEMART_CART_SHIPPING'); ?>:</label>
					<span class="name"><?php echo $PRODUCT_LIST->SHIPMENT->NAME; ?></span>
				</td>
				
				<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
					<?php if ($COL->ID == 'DISCOUNT') { ?>
						<td class="discount"><?php echo $PRODUCT_LIST->SHIPMENT->DISCOUNT; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
						<td class="total_excl_tax"><?php echo $PRODUCT_LIST->SHIPMENT->TOTAL_EXCL_TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TAX') { ?>
						<td class="tax"><?php echo $PRODUCT_LIST->SHIPMENT->TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
						<td class="total_incl_tax">
							<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX']) && $PRODUCT_LIST->SHIPMENT->TOTAL_EXCL_TAX != $PRODUCT_LIST->SHIPMENT->TOTAL_INCL_TAX) { ?>
								<span class="total_excl_tax responsive"><?php echo $PRODUCT_LIST->SHIPMENT->TOTAL_EXCL_TAX; ?></span>
							<?php } ?>
							
							<?php echo $PRODUCT_LIST->SHIPMENT->TOTAL_INCL_TAX; ?>
						</td>
					<?php } ?>
				<?php } ?>
			</tr>
		<?php } ?>

		<?php // Payment ?>

		<?php if ($PRODUCT_LIST->PAYMENT) { ?>
			<tr class="payment">
				<td class="label">
					<label><?php echo JText::_('COM_VIRTUEMART_CART_PAYMENT'); ?>:</label>
					<span class="name"><?php echo $PRODUCT_LIST->PAYMENT->NAME; ?></span>
				</td>

				<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
					<?php if ($COL->ID == 'DISCOUNT') { ?>
						<td class="discount"><?php echo $PRODUCT_LIST->PAYMENT->DISCOUNT; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
						<td class="total_excl_tax"><?php echo $PRODUCT_LIST->PAYMENT->TOTAL_EXCL_TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TAX') { ?>
						<td class="tax"><?php echo $PRODUCT_LIST->PAYMENT->TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
						<td class="total_incl_tax">
							<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX']) && $PRODUCT_LIST->PAYMENT->TOTAL_EXCL_TAX != $PRODUCT_LIST->PAYMENT->TOTAL_INCL_TAX) { ?>
								<span class="total_excl_tax responsive"><?php echo $PRODUCT_LIST->PAYMENT->TOTAL_EXCL_TAX; ?></span>
							<?php } ?>
	
							<?php echo $PRODUCT_LIST->PAYMENT->TOTAL_INCL_TAX; ?>
						</td>
					<?php } ?>
				<?php } ?>
			</tr>
		<?php } ?>

		<?php // Total ?>
		
		<tr class="total">
			<td class="label">
				<?php echo JText::_('COM_VIRTUEMART_CART_TOTAL'); ?>
			</td>
		
			<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
				<?php if ($COL->ID == 'DISCOUNT') { ?>
					<td class="discount"><?php echo $PRODUCT_LIST->TOTAL->DISCOUNT; ?></td>
				<?php } ?>
				
				<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
					<td class="total_excl_tax"><?php echo $PRODUCT_LIST->TOTAL->TOTAL_EXCL_TAX; ?></td>
				<?php } ?>
				
				<?php if ($COL->ID == 'TAX') { ?>
					<td class="tax"><?php echo $PRODUCT_LIST->TOTAL->TAX; ?></td>
				<?php } ?>
				
				<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
					<td class="total_incl_tax">
						<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX']) && $PRODUCT_LIST->TOTAL->TOTAL_EXCL_TAX != $PRODUCT_LIST->TOTAL->TOTAL_INCL_TAX) { ?>
							<span class="total_excl_tax responsive"><?php echo $PRODUCT_LIST->TOTAL->TOTAL_EXCL_TAX; ?></span>
						<?php } ?>
						
						<?php echo $PRODUCT_LIST->TOTAL->TOTAL_INCL_TAX; ?>
					</td>
				<?php } ?>
			<?php } ?>
		</tr>

		<?php // Total in payment currency ?>
		
		<?php if ($PRODUCT_LIST->TOTAL_CURRENCY) { ?>
			<tr class="total_currency">
				<td class="label">
					<?php echo JText::_('COM_VIRTUEMART_CART_TOTAL_PAYMENT'); ?>
				</td>
			
				<?php foreach ($PRODUCT_LIST->PRICE_COLS as $COL) { ?>
					<?php if ($COL->ID == 'DISCOUNT') { ?>
						<td class="discount"><?php echo $PRODUCT_LIST->TOTAL_CURRENCY->DISCOUNT; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_EXCL_TAX') { ?>
						<td class="total_excl_tax"><?php echo $PRODUCT_LIST->TOTAL_CURRENCY->TOTAL_EXCL_TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TAX') { ?>
						<td class="tax"><?php echo $PRODUCT_LIST->TOTAL_CURRENCY->TAX; ?></td>
					<?php } ?>
					
					<?php if ($COL->ID == 'TOTAL_INCL_TAX') { ?>
						<td class="total_incl_tax">
							<?php if (isset($PRODUCT_LIST->PRICE_COLS['TOTAL_EXCL_TAX']) && $PRODUCT_LIST->TOTAL_CURRENCY->TOTAL_EXCL_TAX != $PRODUCT_LIST->TOTAL_CURRENCY->TOTAL_INCL_TAX) { ?>
								<span class="total_excl_tax responsive"><?php echo $PRODUCT_LIST->TOTAL_CURRENCY->TOTAL_EXCL_TAX; ?></span>
							<?php } ?>
							
							<?php echo $PRODUCT_LIST->TOTAL_CURRENCY->TOTAL_INCL_TAX; ?>
						</td>
					<?php } ?>
				<?php } ?>
			</tr>
		<?php } ?>
	
	</tfoot>

</table>
</fieldset>
</div>