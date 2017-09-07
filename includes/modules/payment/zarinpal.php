<?php
/**
 * ZarinPal Payment for Tomato Cart
 * Make Compatible with V1.1.8.6 Tomato Cart By SHT Design Team
 * http://sht-design.ir 
 */
		require_once ('ext/lib/nusoap.php');

		class osC_Payment_zarinpal extends osC_Payment
		{
				var $_title, $_code = 'zarinpal', $_status = false, $_sort_order, $_order_id;

				function osC_Payment_zarinpal()
				{
						global $osC_Database, $osC_Language, $osC_ShoppingCart;

						$this->_title = $osC_Language->get('payment_zarinpal_title');
						$this->_method_title = $osC_Language->get('payment_zarinpal_method_title');
						$this->_status = (MODULE_PAYMENT_ZARINPAL_STATUS == '1') ? true : false;
						$this->_sort_order = MODULE_PAYMENT_ZARINPAL_SORT_ORDER;

						$this->form_action_url = 'https://www.zarinpal.com/pg/StartPay/';

						if ($this->_status === true)
						{
								if ((int) MODULE_PAYMENT_ZARINPAL_ORDER_STATUS_ID > 0)
								{
										$this->order_status = MODULE_PAYMENT_ZARINPAL_ORDER_STATUS_ID;
								}

								if ((int) MODULE_PAYMENT_ZARINPAL_ZONE > 0)
								{
										$check_flag = false;

										$Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
										$Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
										$Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_ZARINPAL_ZONE);
										$Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
										$Qcheck->execute();

										while ($Qcheck->next())
										{
												if ($Qcheck->valueInt('zone_id') < 1)
												{
														$check_flag = true;
														break;
												}
												elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id'))
												{
														$check_flag = true;
														break;
												}
										}

										if ($check_flag === false)
										{
												$this->_status = false;
										}
								}
						}
				}

				function selection()
				{
						return array('id' => $this->_code, 'module' => $this->_method_title);
				}

				function confirmation()
				{
						$this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);
				}


				function process_button()
				{
						global $osC_Currencies, $osC_ShoppingCart, $osC_Language, $osC_Database;

						if (MODULE_PAYMENT_ZARINPAL_CURRENCY == 'Selected Currency')
						{
								$currency = $osC_Currencies->getCode();
						}
						else
						{
								$currency = MODULE_PAYMENT_ZARINPAL_CURRENCY;
						}

						$amount = round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2);
						
						$order = $this->_order_id;

						//curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
						//$page = curl_exec ($ch);

						$client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');


						///////////////// ZARINPAL PAY REQUEST

						$amount = $amount / 10;						//Amount will be based on Toman
						$orderId = $order;
						$callbackUrl = osc_href_link(FILENAME_CHECKOUT, 'process', 'SSL', null, null, true);

						// Check for an error
						$err = $client->getError();
						if ($err)
						{
								echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
								die();
						}

						$parameters = array(
						'MerchantID' => MODULE_PAYMENT_ZARINPAL_PIN,						// this is our PIN NUMBER
						'Amount' => $amount, 
						'CallbackURL' => $callbackUrl, 
						'Description' => $orderId);

						// Call the SOAP method
						$result = $client->call('PaymentRequest', $parameters);
// 	echo '<h2>Fault</h2><pre>';
// 								print_r($result);
// 								echo '</pre>';
// 								die();
						// Check for a fault
						if ($client->fault)
						{
								echo '<h2>Fault</h2><pre>';
								print_r($result);
								echo '</pre>';
								die();
						}
						else
						{
						// Check for errors

								$resultStr = $result['Status'];
								$auth = $result['Authority'];


								$err = $client->getError();
								if ($err)
								{
								// Display the error
										echo '<h2>Error</h2><pre>' . $err . '</pre>';
										die();
								}
								else
								{
								// Display the result

								//$res = explode (',',$resultStr);

								// echo "<script>alert('Pay Response is : " . $resultStr . "');</script>";
								//  echo "Pay Response is : " . $resultStr; //show resultStr in payment page

									//	$au = $resultStr;

										if ($auth == '-1')
										{       
										        osC_Order::remove($this->_order_id);
												echo $osC_Language->get('payment_zarinpal_result_error_1').'<br>';												//show resultStr in payment page
										}
										elseif ($auth == '-2')
										{       
										        osC_Order::remove($this->_order_id);
												echo $osC_Language->get('payment_zarinpal_result_error_2'.'<br>');												//show resultStr in payment page
										}
										elseif ($auth == '-3')
										{       
										        osC_Order::remove($this->_order_id);
												echo $osC_Language->get('payment_zarinpal_result_error_3').'<br>';
										}
										elseif ($auth == '-4')
										{       
										        osC_Order::remove($this->_order_id);
												echo $osC_Language->get('payment_zarinpal_result_error_4').'<br>';												//show resultStr in payment page
										}
										elseif ($auth == '-21')
										{       
										        osC_Order::remove($this->_order_id);
												echo $osC_Language->get('payment_zarinpal_result_error_21').'<br>';												//show resultStr in payment page
										}
										else
										{
										// Update table, Save RefId
										//echo "<script language='javascript' type='text/javascript'>postRefId('" . $res[1] . "');</script>";
										// insert ref id in database

							$osC_Database->simpleQuery("insert into `" . DB_TABLE_PREFIX . "zarinpal_transactions`
					  		(orders_id,receipt_id,transaction_method,transaction_date,transaction_amount,transaction_id) values
		                                        ('$order','$auth','zarinpal','','$amount','')
					                  ");
												//
							echo '<div style="text-align:left;">' . osc_link_object(osc_href_link('https://www.zarinpal.com/pg/StartPay/' . $auth, '', '', '', false), osc_draw_image_button('button_confirm_order.gif', $osC_Language->get('button_confirm_order'), 'id="btnConfirmOrder"')) . '</div>';

										
										}

								}								// end Display the result
						}						// end Check for errors
						//
//error_log( print_r($resultStr, TRUE) );
						$process_button_string .= osc_draw_hidden_field('au', $au);
						return $process_button_string;
				}

				function get_error()
				{
						global $osC_Language;

						return $error;
				}
				function process()
				{
						global $osC_Language, $osC_Customer, $osC_Currencies, $osC_ShoppingCart, $_POST, $_GET, $messageStack, $osC_Database;

						//$refid = $_REQUEST['refID'];
						$au = $_GET['Authority'];
						$status = $_GET['Status'];
						$this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);
						$order = $this->_order_id;
						//error_log( print_r($au, TRUE) );
						if (MODULE_PAYMENT_ZARINPAL_CURRENCY == 'Selected Currency')
						{
								$currency = $osC_Currencies->getCode();
						}
						else
						{
								$currency = MODULE_PAYMENT_ZARINPAL_CURRENCY;
						}
						$amount = round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2);
						if ($au)
						{
						//curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
						//$page = curl_exec ($ch);

								$client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');

								///////////////// VERIFY REQUEST

								$verifyau = $au;
								$verifyamount = $amount / 10;
								//error_log( print_r($verifyau, TRUE) );
								// Check for an error
								$err = $client->getError();
								if ($err)
								{
										echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
										die();
								}

								$parameters = array(
								'MerchantID' => MODULE_PAYMENT_ZARINPAL_PIN, 
								'Authority' => $verifyau, 
								'Amount' => $verifyamount);

								// Call the SOAP method
								$result = $client->call('PaymentVerification', $parameters);
								error_log( print_r($result, TRUE) );

								// Check for a fault
								if ($client->fault)
								{
										echo '<h2>Fault1</h2><pre>';
										print_r($result);
										echo '</pre>';
										die();
								}
								else
								{
										$resultStr = $result['Status'];
										$err = $client->getError();
										if ($err)
										{										
								        // Display the error
										echo '<h2>Error</h2><pre>' . $err . '</pre>';
										die();
										}
								        else
								        {
										if ($resultStr == '100')
										{
										// this is a succcessfull payment
										// we update our DataBase
											//	echo "ZarinPal Response is : " . $resultStr;					//show resultStr in payment page

												//  save transaction_id to database
												$osC_Database->simpleQuery("update `" . DB_TABLE_PREFIX . "zarinpal_transactions` set transaction_id = '$au',transaction_date = '" . date("YmdHis") . "' where 1 and ( receipt_id = '$au' )");
												//
												$Qtransaction = $osC_Database->query('insert into :table_orders_transactions_history (orders_id, transaction_code, transaction_return_value, transaction_return_status, date_added) values (:orders_id, :transaction_code, :transaction_return_value, :transaction_return_status, now())');
												$Qtransaction->bindTable(':table_orders_transactions_history', TABLE_ORDERS_TRANSACTIONS_HISTORY);
												$Qtransaction->bindInt(':orders_id', $order);
												$Qtransaction->bindInt(':transaction_code', 1);
												$Qtransaction->bindValue(':transaction_return_value', $au);
												$Qtransaction->bindInt(':transaction_return_status', 1);
												$Qtransaction->execute();
												//
												$this->_order_id = osC_Order :: insert();
												
												$comments = $osC_Language->get('payment_zarinpal_method_refid').'[' . $au . ']';

												osC_Order :: process($this->_order_id, $this->order_status,$comments);

										}
										else
										{
										//  delete receipt id from database
												$osC_Database->simpleQuery("delete from `" . DB_TABLE_PREFIX . "zarinpal_transactions` where 1 and ( receipt_id = '$au' ) and ( orders_id = '$order' )");
												//

												osC_Order :: remove($this->_order_id);
												if ($resultStr == '-1')
												{
														$messageStack->add_session('checkout', $osC_Language->get('payment_zarinpal_result_error_1'), 'error');
												}
												elseif ($resultStr == '-2')
												{
														$messageStack->add_session('checkout', $osC_Language->get('payment_zarinpal_result_error_2'), 'error');
												}
												elseif ($resultStr == '0')
												{
														$messageStack->add_session('checkout', $osC_Language->get('payment_zarinpal_result_error_0'), 'error');
												}
												elseif ($resultStr == '-11')
												{
														$messageStack->add_session('checkout', $osC_Language->get('payment_zarinpal_result_error_11'), 'error');
												}
												elseif ($resultStr == '-12')
												{
														$messageStack->add_session('checkout', $osC_Language->get('payment_zarinpal_result_error_12'), 'error');
												}

												osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL', null, null, true));
												//

										}
                                   }
								}
						}
						else
						{
						//  delete receipt id from database
								$osC_Database->simpleQuery("delete from `" . DB_TABLE_PREFIX . "zarinpal_transactions` where 1 and ( orders_id = '$order' )");
								//
								// this is a UNsucccessfull payment
								osC_Order :: remove($this->_order_id);

								$messageStack->add_session('checkout', $osC_Language->get('payment_zarinpal_result_error'), 'error');

								osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL', null, null, true));
						}
				}

				function callback()
				{
						global $osC_Database;

						//

				}
		}
?>