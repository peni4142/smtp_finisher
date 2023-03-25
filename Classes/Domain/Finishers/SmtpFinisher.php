<?php
declare(strict_types=1);

namespace PeerNissen\SmtpFinisher\Domain\Finishers;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;


final class SmtpFinisher extends AbstractFinisher
{
	protected PHPMailer $phpMailerLib;

    protected $shortFinisherIdentifier = 'smtp';

    /**
     * @throws Exception
     */
    public function executeInternal() : void
    {
        $mailer = $this->phpMailerLib = new PHPMailer();

		$mailer->isSMTP();
		$mailer->SMTPAuth =true;
		$mailer->SMTPSecure = $this->phpMailerLib::ENCRYPTION_STARTTLS;
		
		$mailer->Port = (int)$this->options["smtpPort"];
		$mailer->Host = $this->options["smtpServer"];

		$mailer->Username = $this->options["username"];
		$mailer->Password = $this->options["password"];

		$mailer->setFrom($this->options["senderAddress"]) ;
        $mailer->addAddress($this->parseOption("recipent"));


		$mailer->isHTML();
		$mailer->Subject = "Hello, World!";
		$mailer->Body = "Hi Test Email";
		$mailer->AltBody = "Hi Test Email";

		$mailer->Send(); 
	}
}
