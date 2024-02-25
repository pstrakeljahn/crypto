<?php

namespace PS\Core\Mail;

use Config;
use PHPMailer\PHPMailer\PHPMailer;
use PS\Core\Logging\Logging;

class MailHelper extends PHPMailer
{
    private bool $hasErrors = false;

    public function __construct()
    {
        $this->CharSet = 'utf-8';
        $this->Host = Config::MAIL_HOST;
        $this->Port = Config::MAIL_PORT;
        $this->Username = Config::MAIL_USERNAME;
        $this->Password = Config::MAIL_PASSWORD;
        $this->SMTPSecure = self::ENCRYPTION_STARTTLS;
        $this->isSMTP();
        $this->SMTPAuth = true;
        foreach ([$this->Host, $this->Port, $this->Username, $this->Password] as $param) {
            if (empty($param) || is_null($param)) {
                Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, 'Mail Credentials have to be set in Config.php');
                $this->hasErrors = true;
                break;
            }
        }
    }

    /**
     * Prepares a Mail, ready to be send
     * 
     * @param string $to Recipient of the mail
     * @param string $subject Subject of the mail
     * @param string $body Body of the mail
     * @param bool $isHtml $body is interpreted as html
     * @param string $altBody Alternative body if display as html fails
     * @return self
     */
    public function createMail(string $to, string $subject, string $body, bool $isHtml = false, string $altBody = ''): ?self
    {
        if ($this->hasErrors) return null;
        $this->addAddress($to);
        $this->Subject = $subject;
        $this->Body    = $body;
        $this->setFrom(Config::MAIL_USERNAME, Config::MAIL_SENDER);
        $this->isHTML($isHtml);
        $this->AltBody = $altBody;
        return $this;
    }

    /**
     * Sends Mail
     * 
     * @return bool
     */
    public function send(): bool
    {
        if ($this->hasErrors) return false;
        try {
            if (parent::send() !== false) {
                Logging::getInstance()->add(Logging::LOG_TYPE_MAIN, 'Mail has been sent to ' . $this->to[0][0]);
                return true;
            } else {
                Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, 'Could not sent Mail to ' . $this->to[0][0]);
                return false;
            }
        } catch (\Exception $e) {
            Logging::getInstance()->add(Logging::LOG_TYPE_ERROR, $e->getMessage());
            return false;
        }
    }
}
