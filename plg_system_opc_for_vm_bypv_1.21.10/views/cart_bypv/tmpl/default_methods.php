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

// Note: This is common template for templates shipment_bypv and payment_bypv. 

/*** TEMPLATE VARIABLES ***/

if (empty($this->METHOD_TYPE) || empty($this->METHODS)) return;

?>

<?php if (!empty($this->METHODS->INFO_HTML)) { ?>
	<div class="info"><?php echo $this->METHODS->INFO_HTML; ?></div>
<?php } ?>

<?php if ($this->METHODS->IS_AUTOMATIC_SELECTED) { ?>

	<p class="automatic"><?php echo $this->METHODS->NAME; ?></p>
	
<?php } else { ?>
	
	<?php if (!empty($this->METHODS->OPTIONS)) { ?>
	
		<ul class="clean">
			<?php foreach ($this->METHODS->OPTIONS as $OPTION) { ?>
				<li id="bypv_cart_<?php echo $this->METHOD_TYPE . '_' . $OPTION->ID; ?>">
					<?php echo $OPTION->HTML; ?>
				</li>
			<?php } ?>
		</ul>
		
	<?php } else { ?>
	
		<p class="not_found"><?php echo $this->METHODS->NOT_FOUND_TEXT; ?></p>
		
	<?php } ?>

<?php } ?>
