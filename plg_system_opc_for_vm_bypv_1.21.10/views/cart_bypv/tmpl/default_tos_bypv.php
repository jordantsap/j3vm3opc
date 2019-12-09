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
$TOS = $this->getTermOfServiceData_byPV();

/*** CSS and JS ***/

if ($CART->VMCFG_USE_FANCY) {
	vmJsApi::js( 'fancybox/jquery.fancybox-1.3.4.pack');
	vmJsApi::css('jquery.fancybox-1.3.4');
	
	$box = "
//<![CDATA[
	jQuery(document).ready(function($) {
		$('div#full-tos').hide();
		var con = $('div#full-tos').html();
		$('a#terms-of-service').click(function(event) {
			event.preventDefault();
			$.fancybox ({ div: '#full-tos', content: con });
		});
	});

//]]>
";
} else {
	vmJsApi::js ('facebox');
	vmJsApi::css ('facebox');
	
	$box = "
//<![CDATA[
	jQuery(document).ready(function($) {
		$('div#full-tos').hide();
		$('a#terms-of-service').click(function(event) {
			event.preventDefault();
			$.facebox( { div: '#full-tos' }, 'my-groovy-style');
		});
	});

//]]>
";
}

$document = JFactory::getDocument ();
$document->addScriptDeclaration ($box);
$document->addStyleDeclaration ('#facebox .content {display: block !important; height: 480px !important; overflow: auto; width: 560px !important; }');

?>

<div id="cart_tos" class="cart_block">
	<fieldset class="clean">
		<input type="hidden" name="<?php echo $TOS->INPUT_NAME; ?>" value="0" />
		<input id="tosAccepted" type="checkbox" name="<?php echo $TOS->INPUT_NAME; ?>" value="1"
			<?php echo ($TOS->IS_ACCEPTED ? 'checked="checked"' : ''); ?>
		/>
	
		<?php if ($CART->VMCFG_SHOW_LEGAL_INFO) { ?>
		
			<label for="tosAccepted">
				<a id="terms-of-service" class="clean" href="<?php $TOS->URL; ?>" rel="facebox" target="_blank">
					<?php echo JText::_('COM_VIRTUEMART_CART_TOS_READ_AND_ACCEPTED'); ?>
				</a>
			</label>
	
			<div id="full-tos">
				<?php $this->printHeader_byPV(2, 'COM_VIRTUEMART_CART_TOS'); ?>
				<?php echo $TOS->CONTENT_HTML; ?>
			</div>
			
		<?php } else { ?>
		
			<label for="tosAccepted">
				<?php echo JText::_('COM_VIRTUEMART_I_AGREE_TO_TOS'); ?>
			</label>
			
		<?php } ?>
	</fieldset>
</div>
