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
<div class="card-block">
    <div class="row">
        <div class="col-md-12">
        <div class="alert alert-success" role="alert">
        <h4 id="netopiaFeedbackTitle" class="alert-heading">{$errors[0]|escape:'htmlall':'UTF-8'}</h4>
        <h5 id="netopiaFeedbackSubTitle" class="alert-heading">{$errors[1]|escape:'htmlall':'UTF-8'}</h5>
        <p id="netopiaFeedbackReference">ID referinta comanda : <strong>{$errors[2]}.<strong></p>
        <p id="netopiaFeedbackTotalPaid">Plata totala : <strong>{$errors[3]}<strong>.</p>
        <hr>
        <p id="netopiaFeedbackExteraInfo" class="mb-0">{$errors[4]}.</p>
        </div>
        </div>
    </div>
</div>
{/block}