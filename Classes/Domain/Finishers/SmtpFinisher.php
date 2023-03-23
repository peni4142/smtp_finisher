<?php declare(strict_types=1);

namespace peni4142\smtp_finisher\Domain\SmtpFinisher;

use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;


final class SmtpFinisher extends AbstractFinisher
{

	protected $phpMailerLib;

	public function __construct(\Vendor\PHPMailer $phpMailerLib)
	{
		$this->phpMailerLib = $phpMailerLib;
	}

	protected $shortFinisherIdentifier = 'smtp';

	public function executeInternal() : void{
		$senderOptoins = $this->parseOptions("sender");
		$mailer = new $this->phpMailerLib->PHPMailer();

		$mailer->isSMTP();
		$mailer->SMTPAuth =true;
		$mailer->SMTPSecure = $this->phpMailerLib::ENCRYPTION_STARTTLS;
		
		$mailer->Port = $senderOptoins["smtpPort"];
		$mailer->Host = $senderOptoins["smtpServer"];

		$mailer->Username = $senderOptoins["username"];
		$mailer->Password = $senderOptoins["password"];

		$mailer->setFrom($senderOptoins["senderAddress"]) ;


		foreach($this->parseOptions("recipents") as $recipent){
			$mailer->addAddress($recipent);
		}

		$mailer->isHTML();
		$mailer->Subject = "Hello, World!";
		$mailer->Body = "Hi Test Email";
		$mailer->AltBody = "Hi Test Email";

		$mailer->Send(); 
	}
}

?>
