<?php

	require_once('../config.php');

	// Set header
	header('Content-Type: application/json');

	// Try database connection
	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	} catch(PDOException $e) {
		echo json_encode(
			array(
				'error' => true,
				'errorCode' => 400,
				'message' => 'We have experienced a problem trying to establish a database connection. Please contact the system administrator should this issue persist.'
			)
		);
		exit();
	}

	if(!isset($_REQUEST) || empty($_REQUEST)) {
		echo json_encode(
			array(
				'error' => true,
				'errorCode' => 400,
				'message' => 'No request detected.'
			)
		);
		exit();
	}

	if($_GET['type']) {
		// Distinguishes different AJAX calls based on 'type'
		$type = intval($_GET['type']);

		switch ($type) {
			// Type 1: Updating LORE1 orders
			case 1:
				// Sanitize
				$OrderID 		= $_GET['id'];
				$OrderSalt		= $_GET['salt'];
				$SeedQuantity 	= $_GET['seed'];
				$AdminSalt		= $_GET['admin'];
				$AdminComments 	= $_GET['com'];
				$InternalComments = $_GET['intcom'];
				$ProcessDate 	= $_GET['date'];

				// If SeedQuantity is empty
				if($SeedQuantity<0 || $SeedQuantity=='') {
					$SeedQuantity = null;
				}

				$processed = false;

				try {
					// Check if particular order is already processed
					// So that we know if we should assign an admin
					$check = $db->prepare("SELECT AdminSalt FROM orders_lines WHERE OrderLineID = :orderid");
					$check->bindParam(':orderid', $OrderID);
					$check->execute();

					$c = $check->fetch(PDO::FETCH_ASSOC);
					if($c['AdminSalt'] === null) {
						$processed = false;
					} else {
						$processed = true;
					}

					// Update
					// - If no admin is assigned, assign current admin
					// - If admin is already assigned, don't do anything
					$update_query = "UPDATE orders_lines SET SeedQuantity = ?, AdminComments = ?, InternalComments = ?, ";
					$update_params = array($SeedQuantity, $AdminComments, $InternalComments);
					if(!$processed) {
						$update_query .= "AdminSalt = ?, ";
						$update_params[] = $AdminSalt;
					}
					if(empty($ProcessDate)) {
						$update_query .= "ProcessDate = NULL";
					} else {
						$update_query .= "ProcessDate = ?";
						$update_params[] = $ProcessDate;
					}
					$update_query .= " WHERE OrderLineID = ?";
					$update_params[] = $OrderID;
					
					$update = $db->prepare($update_query);
					$update->execute($update_params);

					// Return admin username if updated
					$returnAdmin = $db->prepare("SELECT OrderLineID, AdminSalt, auth.UserID, auth.FirstName AS AdminFirstName FROM orders_lines LEFT JOIN auth ON AdminSalt = auth.Salt WHERE OrderLineID = :orderid");
					$returnAdmin->bindParam(':orderid', $OrderID);
					$returnAdmin->execute();

					$adminUser = $returnAdmin->fetch(PDO::FETCH_ASSOC);
					echo(json_encode(array(
						'success' => true,
						'admin' => $adminUser['AdminFirstName']
					)));

				} catch(PDOException $e) {
					$errorInfo = $db->errorInfo();
					echo json_encode(
						array(
							'error' => true,
							'errorCode' => 100,
							'message' => 'MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage()
						)
					);
					exit();
				}

				break;

			// Type 2: Updating download data
			case 2:
				$DownloadID = $_GET['id'];
				$FileDesc 	= $_GET['desc'];
				$FileCount 	= $_GET['count'];

				// If file description is empty
				if($FileCount<0 || $FileCount==null) {
					$FileCount = null;
				}

				try {
					$q = $db->prepare("UPDATE download SET FileDesc = ?, `Count` = ? WHERE FileKey = ?");
					$q->execute(array($FileDesc, $FileCount, $DownloadID));

					// Return success message
					echo json_encode(array(
						'success' => true
					));
					exit();
				} catch(PDOException $e) {
					echo(json_encode(array(
						'error' => true,
						'message' => 'Unable to update file count in database'
					)));
					exit();
				}

				break;

			// Type 3: File deletion
			case 3:
				$FileID = $_GET['id'];

				// Delete file from directory
				try {
					$file = $db->prepare("SELECT * FROM download WHERE FileKey = :fileid");
					$file->bindParam(':fileid', $FileID);
					$file->execute();

					$f = $file->fetch(PDO::FETCH_ASSOC);
					if(unlink(DOC_ROOT.'/data/downloads/'.$f['FileName'])) {
						// File deletion is ok
						$entryremove = $db->prepare("DELETE FROM download WHERE FileKey = :fileid");
						$entryremove->bindParam(':fileid', $FileID);
						$entryremove->execute();

						// Return success message
						echo json_encode(array(
							'success' => true
						));
						exit();

					}

				} catch(PDOException $e) {
					echo(json_encode(array(
						'error' => true,
						'message' => 'File deletion has failed, unable to unlink file.'
					)));
					print_r($e);
				}

				break;

			// Type 4: Sending out shipping email
			case 4:
				// Sanitize
				$OrderSalt = escapeHTML($_GET['key']);

				// Fetch user email from database
				try {
					$q = $db->prepare("SELECT
							ord.FirstName AS FirstName,
							ord.LastName AS LastName,
							ord.Email AS Email,
							ord.Salt AS Salt,
							lin.PlantID AS PlantID,
							lin.SeedQuantity AS SeedQuantity,
							lin.AdminComments AS AdminComments,
							lin.ProcessDate AS ProcessDate
						FROM orders_lines AS lin
						LEFT JOIN orders_unique AS ord ON
							lin.Salt = ord.Salt
						WHERE lin.Salt = :salt AND lin.ProcessDate IS NOT NULL");
					$q->bindParam(':salt', $OrderSalt);
					$q->execute();

					if($q->rowCount() > 0) {
						$plant_array = array(array());
						$i = 0;
						while($d = $q->fetch(PDO::FETCH_ASSOC)) {
							$firstname = $d['FirstName'];
							$lastname = $d['LastName'];
							$email = $d['Email'];
							$salt = $d['Salt'];

							$plant_array[$i][0] = $d['PlantID'];
							$plant_array[$i][1] = $d['SeedQuantity'];
							$plant_array[$i][2] = ($d['AdminComments']=='' ? '-' : $d['AdminComments']);

							$i++;
						}

						// Send mail to user
						$mail = new PHPMailer(true);

						// Generate seeds table
						$seeds_table	= "<table style=\"background-color: #f0f0f0; border: 1px solid #aaa; border-collapse: collapse; line-height: 21px; margin-top: 7px; font-size: 12px;\" cellspacing=\"0\" cellpadding=\"0\">";
						$seeds_table	.= "<tr style=\"font-size: 14px\">";
						$seeds_table	.= "<th style=\"background-color: #aaa; border: 1px solid #aaa; color: #333; padding: 7px 14px; text-shadow: 1px 1px 0 rgba(255,255,255,.25);\">Plant ID</th>\n";
						$seeds_table	.= "<th style=\"background-color: #aaa; border: 1px solid #aaa; color: #333; padding: 7px 14px; text-shadow: 1px 1px 0 rgba(255,255,255,.25);\">Seed Quantity</th>\n";
						$seeds_table	.= "<th style=\"background-color: #aaa; border: 1px solid #aaa; color: #333; padding: 7px 14px; text-shadow: 1px 1px 0 rgba(255,255,255,.25);\">Comments</th>\n";
						$seeds_table	.= "</tr>\n";
						foreach($plant_array as $item) {
							$seeds_table .= "<tr".($item[2]=="-" ? "" : " style=\"background-color: rgba(250,234,130,.5);\"").">";
							foreach($item as $field) {
								$seeds_table .= "<td style=\"border: 1px solid #ccc; padding: 2px 14px;\">".$field."</td>\n";
							}
							$seeds_table .= "</tr>\n";
						}
						$seeds_table	.= "</table>\n";

						// Construct mail
						$mail_generator = new \LotusBase\MailGenerator();
						$mail_generator->set_title('<em>Lotus</em> Base: <em>LORE1</em> seeds shipped');
						$mail_generator->set_header_image('cid:mail_header_image');
						$mail_generator->set_content(array(
							'<h3 style="text-align: center; ">Your <em>LORE1</em> seeds are on the way</h3>
							<p>Dear '.$firstname.',</p>
							<p>Your order has been successfully processed and shipped from our location. The delivery time depends on your geographical location, but it should take no longer than four (4) weeks.</p>
							<p>Your processed order is assigned the following key:<br /><strong>'.$OrderSalt.'</strong></p>
							<p>The complete order is as follow:</p>
							'.$seeds_table.'
							<p>Please <a href="https://lotus.au.dk/meta/citation">refer to the citation guide</a> when citing the use of <em>Lotus</em> Base and the <em>LORE1</em> resource.</p>
							<ul>
								<li>Mun et al. (2016). <em>Lotus</em> Base: An integrated information portal for the model legume <em>Lotus japonicus</em>. <em>Sci. Rep.</em> <a href="http://www.nature.com/articles/srep39447">doi:10.1038/srep39447</a>.</li>
								<li>Małolepszy et al. (2016). The <em>LORE1</em> insertion mutant resource. <em>Plant J.</em> <a href="https://www.ncbi.nlm.nih.gov/pubmed/27322352">doi:10.1111/tpj.13243</a>.</li>
							</ul>
							<p>If you need to return seeds to us after propagating the plant line, please address it to:</p>
							<blockquote>Stig U. Andersen<br />Centre for Carbohydrate Recognition and Signalling<br />Gustav Wieds Vej 10<br />Aarhus C, DK-8000</blockquote>
							<p>Should you require any assistance, or have any enquiries, kindly contact us through the <a href="https://lotus.au.dk/meta/contact.php?key='.$OrderSalt.'">contact form</a> on our site. Your order key has been included in the link. <strong>Do not reply to this email because mails to this account (noreply@mb.au.dk) will not be directed to any staff.</strong></p>
							<p>Yours sincerely,<br /><em>Lotus</em> Base<br />Centre for Carbohydrate Recognition and Signalling<br />Aarhus University<br />Gustav Wieds Vej 10<br />DK-8000 Aarhus C</p>
							'));

						$mail->IsSMTP();
						$mail->IsHTML(true);
						$mail->Host			= SMTP_MAILSERVER;
						$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
						$mail->CharSet		= "utf-8";
						$mail->Encoding		= "base64";
						$mail->Subject		= "LORE1 order shipped";
						$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
						$mail->MsgHTML($mail_generator->get_mail());
						$mail->AddAddress($email, $firstname.' '.$lastname);

						$mail->AddEmbeddedImage(DOC_ROOT."/dist/images/mail/header.jpg", mail_header_image);
						$mail->smtpConnect(
							array(
								"ssl" => array(
									"verify_peer" => false,
									"verify_peer_name" => false,
									"allow_self_signed" => true
								)
							)
						);

						$mail->Send();

						// Write to database
						$update = $db->prepare("UPDATE orders_unique SET ShippingEmail = 1, Shipped = 1, ShippedTimestamp = NOW() WHERE Salt = :ordersalt");
						$update->bindParam(':ordersalt', $OrderSalt);
						$update->execute();

						// If everything is okay, response with okay message
						echo json_encode(array(
							'success' => true
						));

					} else {
						throw new Exception('The order does not exist, or has not been processed fully yet.');
					}

				} catch(PDOException $e) {
					echo(json_encode(array(
						'error' => true,
						'message' => 'A MySQL error has occured when trying to pull data from database.'
					)));
					exit();
				} catch(Exception $e) {
					echo(json_encode(array(
						'error' => true,
						'message' => $e->getMessage()
					)));
					exit();
				} catch(phpmailerException $e) {
					// Mail has failed to send
					echo(json_encode(array(
						'error' => true,
						'message' => 'There is an error sending the email to the receipent.'
					)));
					exit();
				} 

				break;

			// If no cases are met
			default:
				break;
		}

	} else if($_POST['type']) {
		$type = intval($_POST['type']);

		switch ($type) {
			// Type 6: Admin actions
			case 6:

				$action = intval($_POST['action']);
				$scope  = $_POST['scope'];
				$salts  = $_POST['salts'];

				if($scope == 'all' || $scope == 'some') {
					$ordersalts = json_decode($_POST['salts']);
				} else {
					echo json_encode(array(
						'error' => true,
						'message' => 'Item scope is undefined or misdefined.'
					));
					exit();
				}

				try {
					if($action == 4) {
						// Manually verify
						$q = $db->prepare("UPDATE orders_unique SET Verified = 1 WHERE Salt IN (".str_repeat('?,', count($ordersalts)-1)."?".")");
						$q->execute($ordersalts);
					} else if ($action == '5') {
						// Delete from orders_unique and orders_lines tables
						$q1 = $db->prepare("DELETE FROM orders_unique WHERE Salt IN (".str_repeat('?,', count($ordersalts)-1)."?".")");
						$q1->execute($ordersalts);

						$q2 = $db->prepare("DELETE FROM orders_lines WHERE Salt IN (".str_repeat('?,', count($ordersalts)-1)."?".")");
						$q2->execute($ordersalts);
					} else {
						echo json_encode(array(
							'error' => true,
							'message' => 'Admin action is undefined. No database entries have been modified.'
						));
						exit();
					}
				} catch(PDOException $e) {
					$errorInfo = $db->errorInfo();
					echo json_encode(
						array(
							'error' => true,
							'errorCode' => 100,
							'message' => 'MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage()
						)
					);
					exit();
				}
				

				echo(json_encode(array(
					'success' => true,
					'os' => $ordersalts
				)));
				exit();

				break;

			// If no cases are met
			default:
				echo(json_encode(array(
					'error' => true,
					'message' => 'You have tried to perform an illegal operation.'
				)));
				exit();
				break;

		}
	}

?>