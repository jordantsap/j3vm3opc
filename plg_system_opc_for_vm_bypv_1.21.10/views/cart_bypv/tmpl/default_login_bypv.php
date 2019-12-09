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
$LOGIN = $this->getLoginData_byPV();

?>

<div id="bypv_cart_login" class="cart_block <?php echo ($LOGIN->IS_USER_LOGGED ? 'logout' : 'login'); ?>">
	<?php $this->printHeader_byPV(2, 'PLG_SYSTEM_OPC_FOR_VM_BYPV_LOGIN_TITLE'); ?>

	<fieldset class="clean">
		<?php if (!$LOGIN->IS_USER_LOGGED) { ?>
		
			<?php // Login Dialog ?>
			
			<table class="clean">
				<tbody>
					<tr class="username">
						<td class="label"><label for="cart_username"><?php echo JText::_('COM_USERS_LOGIN_USERNAME_LABEL'); ?></label></td>
						<td class="value"><input id="cart_username" name="username" type="text" autocomplete="<?php echo $LOGIN->INPUT_USERNAME_AUTOCOMPLETE; ?>" /></td>
					</tr>
	
					<tr class="password">
						<td class="label"><label for="cart_password"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label></td>
						<td class="value"><input id="cart_password" name="password" type="password" autocomplete="<?php echo $LOGIN->INPUT_PASSWORD_AUTOCOMPLETE; ?>" /></td>
					</tr>
				
					<?php if ($LOGIN->IS_REMEMBER_ALLOWED) { ?>
						<tr class="remember">
							<td class="label"></td>
							<td class="value">
								<input id="cart_remember" name="remember" type="checkbox" value="yes" />
								<label for="cart_remember"><?php echo JText::_('JGLOBAL_REMEMBER_ME'); ?></label>
							</td>
						</tr>
					<?php } ?>
					
					<tr class="action">
						<td class="label"></td>
						<td class="value"><input id="bypv_login" type="button" class="text_button" value="<?php echo JText::_('JLOGIN'); ?>" /></td>
					</tr>
				</tbody>

				<tfoot>
					<tr class="reset">
						<td class="label"></td>
						<td class="value">
							<ul class="clean">
								<li>
									<a class="clean" href="<?php echo $LOGIN->LOGIN_RESET_URL ?>"><?php echo JText::_('COM_USERS_LOGIN_RESET'); ?></a>
								</li>
								<li>
									<a class="clean" href="<?php echo $LOGIN->LOGIN_REMIND_URL; ?>"><?php echo JText::_('COM_USERS_LOGIN_REMIND'); ?></a>
								</li>
							</ul>
						</td>
					</tr>
				</tfoot>
			</table>
			
		<?php } else { ?>
		
			<?php // Logout Dialog ?>

			<p><?php echo JText::sprintf('COM_USERS_PROFILE_WELCOME', $LOGIN->USER_NAME); ?></p>
			<input id="bypv_logout" type="button" class="text_button" value="<?php echo JText::_('JLOGOUT'); ?>" />
			
			<div id="bypv_logout_params" style="display: none;">
				<input type="hidden" name="return" value="<?php echo base64_encode('index.php?option=com_virtuemart&view=cart'); ?>" />
				<?php echo JHtml::_('form.token'); ?>
			</div>
		
		<?php } ?>
	</fieldset>	
</div>
