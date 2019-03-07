<?php

class HtmlMail
{
	private $toAddress;
	private $toName;
	  
	private $mail;
	private $outCharset="gbk";

	public function __construct($hostName, $fromAddress, $fromName, $user = null, $pass = null)
	{
//		$fromName = $this->strConv($fromName);
//		$fromAddress = $this->strConv($fromAddress);
		
		$this->mail = new HtmlMimeMail5();
		
		$auth = isset($user);
        $this->mail->setCRLF("\r\n");
		$this->mail->setSMTPParams($hostName, 25, 'qihoo.net', false, $user, $pass);
		$this->mail->setHTMLCharset($this->outCharset);
		$this->mail->setTextCharset($this->outCharset);
		$this->mail->setHeadCharset($this->outCharset);
        $this->mail->setTextEncoding(new Base64Encoding());
        $this->mail->setHtmlEncoding(new Base64Encoding());
        $this->mail->setTextWrap(76);
        $this->mail->setPriority('normal');
        $this->mail->setReturnPath("<$fromAddress>");
		
		$this->mail->setFrom("\"$fromName\" <$fromAddress>");
        $this->mail->setHeader("X-Mailer", "CnMail <qihoo.net>"); 
       
	}
	
	private function strConv($str)
	{
	    return mb_convert_encoding($str, $this->outCharset, 'utf-8');
	}
	
	public function build($toAddress, $toName, $subject, $htmlBody, $imgDir = null, $filepath = null)
	{
//		$toAddress = $this->strConv($toAddress);
//		$toName = $this->strConv($toName);
//		$subject = $this->strConv($subject);
//		$htmlBody = $this->strConv($htmlBody);	
		
//		$htmlBody = str_ireplace("charset=utf-8","charset=".$this->outCharset,$htmlBody);
		
		$this->toAddress = $toAddress;
		$this->toName = $toName;

		$this->mail->setSubject($subject);
		$content = $this->strConv('这是一封网页(HTML)格式的邮件,请使用支持HTML格式的邮件客户端阅读此邮件.');
		$this->mail->setText($content);
		$this->mail->setHTML($htmlBody, $imgDir);
		if(!empty($filepath))
		{
			$this->mail->addAttachment(new fileAttachment($filepath));
		}
		$this->mail->build();
	}
	
	public function getMailAsString()
	{
		return $this->mail->getRFC822(array("{$this->toName}<{$this->toAddress}>"));
	}

	public function save($emlName)
	{
		return file_put_contents($emlName, $this->getMailAsString());
	}

	public function send()
	{
        if (!empty($this->toName))
		    return $this->mail->send(array("\"{$this->toName}\" <{$this->toAddress}>"), 'smtp');
        else
            return $this->mail->send(array($this->toAddress), 'smtp');
	}
    public function multiSend($toArray)
    {
        return $this->mail->send($toArray, 'smtp');
    }
    public function getErrorInfo()
    {
        return $this->mail->getErrorInfo();
    }
}

?>
