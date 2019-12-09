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

// Note: This is common template for templates billing_address_bypv and shipping_address_bypv.

/*** TEMPLATE VARIABLES ***/

$CART = $this->getCartData_byPV();
if (empty($this->ADDRESS_FIELDS)) return;

?>

<?php foreach ($this->ADDRESS_FIELDS->GROUPS as $GROUP_ID => $GROUP) { ?>
	<div class="<?php echo $GROUP_ID . '_group'; ?>" <?php if (!$GROUP->SHOW) echo 'style="display: none;"'; ?>>
		<?php if (!empty($GROUP->TITLE)) { ?>
			<?php $this->printHeader_byPV(3, $GROUP->TITLE); ?>
		<?php } ?>
			
		<table class="bypv_fields clean">
			<?php foreach ($GROUP->FIELDS as $FIELD_ID => $FIELD) { ?>
			
				<tr class="<?php echo $FIELD_ID; ?>_field<?php if ($FIELD->REQUIRED) echo ' bypv_required'; ?>"
					<?php if ($FIELD->DESCRIPTION) echo 'title="' . htmlspecialchars($FIELD->DESCRIPTION) . '"'; ?>
					<?php if (!$FIELD->SHOW) echo 'style="display: none;"'; ?>
				>
					<td class="label">
						<label for="<?php echo $FIELD->NAME; ?>_field">
							<?php echo JText::_($FIELD->TITLE); ?>
						</label>
					</td>
		
					<td class="value">
						<?php if ($CART->IS_PHASE_CHECKOUT) { ?>
						
							<?php echo $FIELD->FORMCODE; ?>
						
						<?php } else { ?>
						
							<?php echo $FIELD->FORMCODE_PREVIEW; ?>
															
						<?php } ?>
					</td>
				</tr>
				
			<?php } ?>
		</table>
	</div>
<?php } ?>
