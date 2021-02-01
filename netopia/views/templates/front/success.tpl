{*
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{extends "$layout"}

{block name="content"}
	<section>
		<div class="panel">
			<div class="row netopia-header">
				<img src="https://suport.mobilpay.ro/np-logo-blue.svg" class="col-xs-6 col-md-4 text-center" id="payment-logo" style="width: 280px;" />
			</div>
			<hr />
			<div class="netopia-content">
				<div class="row">
					<div>
						<h2>{l s='Payment Status' mod='netopia'}</h2>
						<ul class="alert alert-info">
							<li>{l s='Message #1 test' mod='netopia'}.</li>
						</ul>
						<ul class="alert alert-warning">
							<li>{l s='Message #2 test' mod='netopia'}.</li>
						</ul>
						<ul class="alert alert-success">
							<li>{l s='Message #3 test' mod='netopia'}.</li>
						</ul>
					</div>
				</div>

				<hr />

				<div class="row">
					<div class="col-md-12">
						<h4>{l s='Accept payments in all major credit cards in Romania' mod='netopia'}</h4>

						<div class="row">
							<img src="https://netopia-payments.com/core/assets/5993428bab/images/svg/visa.svg" class="col-md-6" id="visa-logo" style="width: 150px;" />
							<img src="https://netopia-payments.com/core/assets/5993428bab/images/svg/mastercard.svg" class="col-md-6" id="master-logo" style="width: 120px;" />
							<div class="col-md-6">
								<h6 class="text-branded">{l s=''}</h6>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
{/block}
