{*
* 2007-2021 PrestaShop
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
*	@author PrestaShop SA <contact@prestashop.com>
*	@copyright	2007-2021 PrestaShop SA
*	@license		http://opensource.org/licenses/afl-3.0.php	Academic Free License (AFL 3.0)
*	International Registered Trademark & Property of PrestaShop SA
*}
  <style>
.rockpay_outer .justify-content-center {
	-webkit-box-pack: center !important;
	justify-content: center !important;
	display: flex;
	flex-wrap: wrap;
	margin-right: -.9375rem;
	margin-left: -.9375rem;
}
.rockpay_outer .col-xl-10 {
	-webkit-box-flex: 0;
	-ms-flex: 0 0 83.33333%;
	flex: 0 0 83.33333%;
	max-width: 83.33333%;
	padding-right: .9375rem;
	padding-left: .9375rem;
}
.rockpay_outer .card{
  position: relative;
  display: block;
  margin-bottom: .625rem;
  background-color: #fff;
  border: 1px solid #dbe6e9;
  border-radius: 5px;
  -webkit-box-shadow: 0 0 4px 0 rgba(0,0,0,0.06);
  box-shadow: 0 0 4px 0 rgba(0,0,0,0.06);
}
.rockpay_outer .form-group {
	width: 100%;
	display: inline-block;
}
.rockpay_outer .card #settings {
	display: flex;
	justify-content: center;
}
.rockpay_outer .form-group .control-label {
	text-align: right;
	font-family: "Open Sans",helvetica,arial,sans-serif;
	font-weight: 400;
	font-size: 14px;
}
.rockpay_outer .panel-footer {
	display: inline-block;
	width: 100%;
	background: transparent;
	border: none;
	padding: 0;
}
.rockpay_outer {
	overflow: hidden;
	width: 100%;
	display: inline-block;
}
.rockpay_outer .panel-footer .btn.btn-default.pull-right {
	margin-left: 30px;
}	  
.rockpay_outer .panel-footer {
	padding: .625rem .9375rem;
	background-color: #fafbfc;
	border-top: 1px solid #dbe6e9;
	border-radius: 0 0 5px 5px;
	width: 100%;
	display: inline-block;
	position: relative;
    top: 5px;
}
.rockpay_outer .form-group.inner_form {
	margin: 0;
}
.rockpay_outer .card-header {
	padding: .625rem .625rem;
	font-weight: 600;
	line-height: 1.5rem;
	background-color: #fafbfc;
	border-bottom: 1px solid #dbe6e9;
	border-radius: 5px 5px 0 0;
	width: 100%;
	display: inline-block;
	margin: 0 0 23px !important;
}	  
.rockpay_outer .card-header {
	padding: .625rem .625rem;
	border-radius: 5px 5px 0 0;
	width: 100%;
	display: flex;
	margin: 0 0 23px !important;
}	  
</style>
            {if !empty($errors)}
            <fieldset style="display:block;">
                <legend>Errors</legend>
                <table cellspacing="0" cellpadding="0" class="stripe-technical">
                        <tbody>
                    {foreach $errors as $error} 
                        <tr>
                           
                            <td style="color:red">{$error|escape:'htmlall':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                </tbody></table>
            </fieldset>
             {/if}

             {if isset($api_success)}
              {if $api_success }
                <div class="conf confirmation alert alert-success">{l s='API connected' mod='rokpay'}</div>
             {elseif $api_success eq 0}
              <div class="conf confirmation alert alert-danger">{l s='Error' mod='rokpay'}</div>
              {/if}
             {/if}

        {if $success}<div class="conf confirmation alert alert-success">{l s='Settings successfully saved' mod='rokpay'}</div>{/if}
        <form action="" method="post">
		<div class="rockpay_outer">
		<div class="row justify-content-center">
      <div class="col-xl-10">
        <div class="card">
			
        <fieldset id="settings">
        
        <div class="form-group inner_form">
			  <h3 class="card-header">
            <i class="material-icons">edit</i> RokPay
          </h3>
      <div class="form-group">
        <label class="control-label col-lg-5" for="simple_product">{l s='Shop number' mod='rokpay'}:</label>
        <div class="col-lg-5">
                <input type="text" name="ROKPAY_SHOP_NUMBER" value="{Configuration::get('ROKPAY_SHOP_NUMBER')|escape:'htmlall':'UTF-8'}" placeholder="Rokpay shop number"  />
        </div></div>
      

        <div class="form-group">
        <label class="control-label col-lg-5" for="simple_product">{l s='Rokpay key' mod='rokpay'}:</label>
        <div class="col-lg-5">
            <input type="password" name="ROKPAY_API_KEY" value="{Configuration::get('ROKPAY_API_KEY')|escape:'htmlall':'UTF-8'}" placeholder="Shop API Key" />
        </div></div>
            <div class="form-group">
        <label class="control-label col-lg-5" for="simple_product">{l s='Environment' mod='rokpay'}:</label>
        <div class="col-lg-5">
         <select class="form-control" id="environment" name="ROKPAY_API_ENVIRONMENT">
              
            <option value="{$staging}" {if $selected_env eq $staging}selected{/if}>Staging</option>

            <option value="{$production}" {if $selected_env eq $production}selected{/if}>Production</option>
     
        </select>
        </div></div>
        <div class="panel-footer">
                 <button type="submit" name="btnTestAPI" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Test Keys' mod='rokpay'}</button>
                <button type="submit" name="btnSubmit" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save' mod='rokpay'}</button>
            </div>
        </fieldset>
			</fieldset></div>	</div></div></div>
		
        </form>
                    
            