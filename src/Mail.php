<?php

namespace OS\MimozaCore;

use PDO;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Includes\Project\Constants;


class Mail
{
	private Database $database;
	private Functions $functions;
	private Core $system;
	public string $address;
	public string $subject;
	public string $message;
	public ?string $sender_name = null;
	public int $sent_status = 0;
	public ?string $extra_log = null;
	public int $send_type = 1;
    public ?string $sendDebug = null;
    public ?string $debug_mail = null;

	public function __construct($database)
	{
		$this->database = $database;
		$this->functions = new Functions();
		$this->system = new Core();
	}

	public function send()
	{
		global $settings;
		if (empty($this->sender_name)) {
			$this->sender_name = $settings->smtp_send_name_surname;
		}
		if ((int)$settings->mail_send_mode !== 1) {
			exit("mail gonderimi aktif degildir");
		}

		// Instantiation and passing `true` enables exceptions
		$mail = new PHPMailer(true);

		try {
			$mail->SMTPDebug = SMTP::DEBUG_OFF;
			$mail->isSMTP();
			$mail->Host = $settings->smtp_host;
			$mail->SMTPAuth = true;
			$mail->Username = $settings->smtp_email;
			$mail->Password = $settings->smtp_password;
			$mail->SMTPSecure = $settings->smtp_secure;
			$mail->Port = $settings->smtp_port;
			$mail->SetLanguage($_SESSION["lang"], 'phpmailler/language/');
			$mail->SetFrom($settings->smtp_send_email_adres, $this->sender_name);
			$mail->CharSet = PHPMailer::CHARSET_UTF8;
			$mail->addReplyTo($settings->smtp_send_email_reply_adres, $this->sender_name);

			if ((int)$settings->smtp_mail_send_debug === 2) {
				$sendAdress = $settings->smtp_send_debug_adres;
                $this->sendDebug = $settings->smtp_send_debug_adres;
			} else {
				$sendAdress = $this->address;
			}
			$mail->AddAddress($sendAdress);


			//maile ait sabit resim yükleniyor
			if (!empty($settings->mail_tempate_logo) && file_exists(Constants::fileTypePath["project_image"]["full_path"] . $settings->mail_tempate_logo)) {
				$mail->addEmbeddedImage(Constants::fileTypePath["project_image"]["full_path"] . $settings->mail_tempate_logo, "img_header", $settings->mail_tempate_logo);
			}

			// Content
			$mail->isHTML();

			//mail teması
			include $this->system->path("includes/MailTemplate/MailTemplate.php");
			$mailTemplate = str_replace(array("[MESSAGE]", "[SITE_URL]", "[SITE_URL]"), array($this->message, $this->system->url(), $this->system->url()), $mailTemplate);
			$mail->Subject = $settings->project_name .' '. $this->subject;
            $mail->msgHTML($mailTemplate);
			$mail->send();
			$this->sent_status = 1;
			$this->log();
			return true;
		} catch (Exception $e) {
			//mail gönderilememişse çalışır
			$this->extra_log = $mail->ErrorInfo;
			$this->sent_status = 2;
			$this->log();
			return false;
		}
	}

	/**
	 * @throws Exception
	 */
	public function sendMailing(int $id): array
	{
		global $settings;
		$result = [];
		//gönderilecek mailingi seçelim
		$query = $this->database::query("SELECT * FROM mailing WHERE id=:id AND status=1 AND deleted=0");
		$query->bindParam(":id", $id, PDO::PARAM_INT);
		$query->execute();
		if ($query->rowCount() !== 1) {
			$result["result"] = 1;
			$result["message"][] = "Mailing bulunamadı.";
			return $result;
		}
		$query_data = $query->fetch(PDO::FETCH_OBJ);

		//mailing tamamlanmış mı
		if ((int)$query_data->completed === 1) {
			$result["result"] = 1;
			$result["completed"] = 1;
			$result["message"][] = "Bu mailing tamamlanmış.";
			return $result;
		}

		//eğer hiç mesaj yoksa kullanıcı aramaya başlayalım
		if (empty($result)) {
			//try_to_send = tekrar göndermeyi deneme sayısı 3 e kadar olanları tekrar dene
			//mailing listesine bağlanılıp kullanıcıları çekiyoruz
			$mailing_user = $this->database::query("SELECT * FROM mailing_user WHERE mailing_id=:m_id AND (send=3 OR send=2) AND try_to_send<3 AND status=1 AND deleted=0 ORDER BY id ASC LIMIT 0,1");
			$mailing_user->bindParam(":m_id", $id, PDO::PARAM_INT);
			$mailing_user->execute();
			$mailing_user_count = $mailing_user->rowCount();
			$mailing_user_data = $mailing_user->fetch(PDO::FETCH_OBJ);

			if (!$this->functions->isEmail($mailing_user_data->email)) {
				//geçersiz maili sil
				$mail_delete = $this->database::query("UPDATE mailing_user SET deleted=1 WHERE id=:id");
				$mail_delete->bindParam(":id", $mailing_user_data->id, PDO::PARAM_INT);
				$mail_delete->execute();

				$result["result"] = 1;
				$result["repeat"] = 1;
				$result["error_mail"] = $mailing_user_data->email;
				return $result;
			}

			//bize sadece 1 kayıt gerekli
			if ((int)$mailing_user_count === 1) {
				// error_reporting(E_STRICT | E_ALL);
				//date_default_timezone_set('Etc/UTC');

				//Create a new PHPMailer instance
				$mail = new PHPMailer(true);
				$mail->isSMTP();
				//charset
				$mail->CharSet = PHPMailer::CHARSET_UTF8;

				$mail->SMTPDebug = SMTP::DEBUG_OFF;
				//Set the hostname of the mail server
				$mail->Host = $settings->smtp_host;
				//Set the SMTP port number - likely to be 25, 465 or 587
				$mail->Port = $settings->smtp_port;
				//Whether to use SMTP authentication
				$mail->SMTPAuth = true;
				//$mail->SMTPKeepAlive = true; // Gönderilen her e-postadan sonra SMTP bağlantısı kapanmaz, SMTP ek yükünü azaltır
				//Username to use for SMTP authentication
				$mail->Username = $settings->smtp_email;
				//Password to use for SMTP authentication
				$mail->Password = $settings->smtp_password;
				//Set who the message is to be sent from
				$mail->setFrom($settings->smtp_send_email_adres, $settings->smtp_send_name_surname);
				//Set an alternative reply-to address
				$mail->addReplyTo($settings->smtp_send_email_reply_adres, $settings->smtp_send_name_surname);

				$mail->setLanguage($_SESSION["lang"], $this->system->url("vendor/phpmailer/language/"));
				$this->subject = $query_data->subject;
				$mail->Subject = $this->subject;

				//mail teması
				include_once $this->system->path("includes/MailTemplate/MailTemplate.php");
				$body = $mailTemplate;

				$mailBody = $query_data->text;

				if (!empty($query_data->image)) {
					$image_decode = unserialize($query_data->image);
					foreach ($image_decode as $image_key => $image_row) {

						if (strpos($mailBody, "image_" . $image_key) !== false) {
							$mail->addEmbeddedImage(Constants::fileTypePath["mailing"]["full_path"] . $image_row, "image_" . $image_key, $image_row);

						}
					}
				}

				$this->message = $mailBody;

				//maile ait sabit resim yükleniyor
				if (!empty($settings->mail_tempate_logo) && file_exists(Constants::fileTypePath["project_image"]["full_path"] . $settings->mail_tempate_logo)) {
					$mail->addEmbeddedImage(Constants::fileTypePath["project_image"]["full_path"] . $settings->mail_tempate_logo, "img_header", $settings->mail_tempate_logo);
				}

				//ekler ekleniyor
				if (!empty($query_data->attachment)) {
					$mailing_attachment = unserialize($query_data->attachment);
					foreach ($mailing_attachment as $m_attachment) {
						if (file_exists(Constants::fileTypePath["mailing_attachment"]["full_path"] . $m_attachment)) {
							$mail->addAttachment(Constants::fileTypePath["mailing_attachment"]["full_path"] . $m_attachment, $m_attachment);
						}
					}
				}
				$body = str_replace(array("[MESSAGE]", "[AD]", "[SOYAD]", "[SITE_URL]"), array($this->message, $mailing_user_data->name, $mailing_user_data->surname, $this->system->url()), $body);

				//Same body for all messages, so set this before the sending loop
				//If you generate a different body for each recipient (e.g. you're using a templating system),
				//set it inside the loop
				$mail->msgHTML($body);

				if ((int)$settings->smtp_mail_send_debug === 2) {
                    $sendAdress = $settings->smtp_send_debug_adres;
                    $this->sendDebug = $settings->smtp_send_debug_adres;
				} else {
                    $sendAdress = $mailing_user_data->email;
				}
                $this->address = $mailing_user_data->email;

				$mail->addAddress($sendAdress, $mailing_user_data->name . " " . $mailing_user_data->surname);

				if ($mail->send()) {
					if ((int)$query_data->completed === 0) {
						//henüz mail bekliyor şimdi başladı ve bunu işleyelim şuan için tamamlanmadı olarak işliyoruz
						$mailing_basladi = $this->database::query("UPDATE mailing SET completed=2 WHERE id=:ma_id");
						$mailing_basladi->bindParam(":ma_id", $query_data->id, PDO::PARAM_INT);
						$mailing_basladi->execute();
					}

					$send_date = date("Y-m-d H:i:s");
					//mail atılan satırı güncelleyelim
					$mail_gonderildi = $this->database::query("UPDATE mailing_user SET send=1,send_date=:s_date WHERE id=:u_id");
					$mail_gonderildi->bindParam(":u_id", $mailing_user_data->id, PDO::PARAM_INT);
					$mail_gonderildi->bindParam(":s_date", $send_date, PDO::PARAM_STR);
					$mail_gonderildi->execute();

					//bu mailge ait gönderilmiş mail sayısı
					$mailing_user_send_info = $this->database::query("SELECT * FROM mailing_user WHERE send=1 AND mailing_id=:id AND deleted=0 AND status=1");
					$mailing_user_send_info->bindParam(":id", $query_data->id, PDO::PARAM_INT);
					$mailing_user_send_info->execute();
					$mailing_user_send_count = $mailing_user_send_info->rowCount();

					//bu mailinge ait gitmesi gereken mail sayısı
					$mailing_user_send_info_2 = $this->database::query("SELECT * FROM mailing_user WHERE mailing_id=:id AND deleted=0 AND status=1");
					$mailing_user_send_info_2->bindParam(":id", $query_data->id, PDO::PARAM_INT);
					$mailing_user_send_info_2->execute();
					$mailing_user_send_count_2 = $mailing_user_send_info_2->rowCount();

					$this->sent_status = 1;
					$this->log();

					//mail atıldı devam etsin
					$result["result"] = 1;
					$result["repeat"] = 1;
					$result["ok_send"] = 1;
					$result["ok_email"] = $mailing_user_data->email;


					//Clear all addresses and attachments for the next iteration
					$mail->clearAddresses();
					$mail->clearAttachments();

					//mailing tamamlandı mı kontrol et
					if ($mailing_user_send_count_2 === $mailing_user_send_count) {
						$result = $this->getResult($query_data, $result);
						$result["message"][] = "Mailing tamamlandı. Sayfayı yenileyiniz.";

						//mailing tamamlandı şimdi gönderilip gönderilmeyenleri işleyelim
						$mail_list = $this->database::query("SELECT * FROM mailing_user WHERE mailing_id=:m_id AND status=1 AND deleted=0");
						$mail_list->bindParam(":m_id", $query_data->id, PDO::PARAM_INT);
						$mail_list->execute();
						$mail_data = $mail_list->fetchAll(PDO::FETCH_OBJ);
						foreach ($mail_data as $mail_data_row) {
							if ($mail_data_row->send === 1) {
								$result["success_send"][] = $mail_data_row->email;
							} elseif ($mail_data_row->send === 2) {
								$result["error_send"][] = $mail_data_row->email;
							} elseif ($mail_data_row->send === 0) {
								$result["wait_mail"][] = $mail_data_row->email;
							}
						}

						if (isset($result["repeat"])) {
							unset($result["repeat"]);
						}
						return $result;
					}

				} else {
					//mail atılamayan satırı güncelleyelim
					$mail_gonderilemedi = $this->database::query("UPDATE mailing_user SET send=2,try_to_send=try_to_send+1 WHERE id=:id");
					$mail_gonderilemedi->bindParam(":id", $mailing_user_data->id, PDO::PARAM_INT);
					$mail_gonderilemedi->execute();

					$this->sent_status = 2;
					$this->extra_log = $mail->ErrorInfo;

					$this->log();

					//Reset the connection to abort sending this message
					$mail->getSMTPInstance()->reset();

					//Clear all addresses and attachments for the next iteration
					$mail->clearAddresses();
					$mail->clearAttachments();

					//mail atılamadı devam etsin
					$result["result"] = 1;
					$result["repeat"] = 1;
					$result["no_send"] = 1;
					$result["no_email"] = $mailing_user_data->email;

				}
				return $result;
			}

		}
		//eğer buraya girerse listede 0 ve 2 yok hepsi 1 dir buda mailing tamamlandı demektir.
		$result = $this->getResult($query_data, $result);
		$result["message"][] = "Mail gönderilecek kullanıcı bulunamadı.";
		$result["message"][] = "Mailing tamamlandı.";
		return $result;
	}

	/**
	 * It's log mail status to mail_log table
	 *
	 */
	private function log(): void
	{
		$db_data = array();
		$db_data["mail"] = $this->address;
		$db_data["send_debug"] = $this->sendDebug;
		$db_data["subject"] = $this->subject;
		$db_data["message"] = $this->message;
		$db_data["sent_status"] = $this->sent_status;
		$db_data["send_type"] = $this->send_type;
		$db_data["extra_log"] = $this->extra_log;
		$this->database::insert("mail_log", $db_data);
	}

	/**
	 * @param object $query_data
	 * @param array $result
	 * @return array
	 */
	private function getResult(object $query_data, array $result): array
	{
		$c_date = date("Y-m-d H:i:s");
		$mailing_tamamlandi = $this->database::query("UPDATE mailing SET completed=1,completed_date=:c_date WHERE id=:ma_id");
		$mailing_tamamlandi->bindParam(":ma_id", $query_data->id, PDO::PARAM_INT);
		$mailing_tamamlandi->bindParam(":c_date", $c_date, PDO::PARAM_STR);
		$mailing_tamamlandi->execute();

		$result["result"] = 1;
		$result["completed"] = 1;
		return $result;
	}

}