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
$COMMENT = $this->getCommentData_byPV();

?>

<div id="cart_comment" class="cart_block">
	<?php $this->printHeader_byPV(2, 'COM_VIRTUEMART_COMMENT_CART'); ?>
	
	<fieldset class="clean">
		<?php if ($CART->IS_PHASE_CHECKOUT) { ?>
		
			<textarea name="customer_comment" cols="60" rows="1"><?php echo $COMMENT->TEXT; ?></textarea>
			
		<?php } else { ?>
		
			<p><?php echo nl2br($COMMENT->TEXT); ?></p>
			
		<?php } ?>
	</fieldset>		
</div>
