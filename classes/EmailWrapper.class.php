<?php
/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/


/**
 * Singleton PHPMailer wrapper.
 *
 * TODO should this class handle an email FIFO to avoid concurence ?
 *
 * @author MantisBT, CodevTT
 */
class EmailWrapper {
   private static $logger;

   /**
    * Singleton
    * @var EmailWrapper
    */
   private static $instance;

   private $phpMailer;

   /**
    * The singleton pattern
    * @static
    * @return IssueCache
    */
   public static function getInstance() {
      if (NULL == self::$instance) {
         self::$instance = new EmailWrapper();
      }
      return self::$instance;
   }

   private function __construct() {

      try {
         self::$logger = Logger::getLogger(__CLASS__);

         $this->phpMailer = new PHPMailer( true );

      } catch (Exception $e) {
         self::$logger->error('EmailWrapper constructor: '.$e->getMessage());
         throw $e;
      }
   }

    public function __destruct() {
      self::$logger->error('EmailWrapper destructor.');

      if( Constants::PHPMAILER_METHOD_SMTP == Constants::$emailSettings['phpMailer_method']) {
         $this->smtpClose();
      }
    }


   /**
    * This function sends an email message based on the supplied email data.
    *
    * @author MantisBT
    * @param EmailData $p_email_data Email Data object representing the email to send.
    * @return boolean
    */
   public function sendEmail( EmailData $p_email_data ) {

      $t_email_data = $p_email_data;

      $t_recipient = trim( $t_email_data->email );
      $t_subject = string_email( trim( $t_email_data->subject ) );
      $t_message = string_email_links( trim( $t_email_data->body ) );

      if( isset( $t_email_data->metadata['hostname'] ) ) {
         $this->phpMailer->Hostname = $t_email_data->metadata['hostname'];
      }

      # Select the method to send mail
      switch( Constants::$emailSettings['phpMailer_method'] ) {
         case Constants::PHPMAILER_METHOD_MAIL:
            $this->phpMailer->IsMail();
            break;

         case Constants::PHPMAILER_METHOD_SENDMAIL:
            $this->phpMailer->IsSendmail();
            break;

         case Constants::PHPMAILER_METHOD_SMTP:
            $this->phpMailer->IsSMTP();

            # SMTP collection is always kept alive
            $this->phpMailer->SMTPKeepAlive = true;

            if( !Toos::is_blank( Constants::$emailSettings['smtp_username'] ) ) {
               # Use SMTP Authentication
               $this->phpMailer->SMTPAuth = true;
               $this->phpMailer->Username = Constants::$emailSettings['smtp_username'];
               $this->phpMailer->Password = Constants::$emailSettings['smtp_password'];
            }

            if( !Toos::is_blank( Constants::$emailSettings['smtp_connection_mode'] ) ) {
               $this->phpMailer->SMTPSecure = Constants::$emailSettings['smtp_connection_mode'];
            }

            $this->phpMailer->Port = Constants::$emailSettings['smtp_port'];

            break;
         default:
            self::$logger->error( 'Unknown phpMailer_method - ' . Constants::$emailSettings['phpMailer_method'] );
      }

      $this->phpMailer->IsHTML( false );              # set email format to plain text
      $this->phpMailer->WordWrap = 80;              # set word wrap to 80 characters
      $this->phpMailer->Priority = $t_email_data->metadata['priority'];  # Urgent = 1, Not Urgent = 5, Disable = 0
      $this->phpMailer->CharSet = $t_email_data->metadata['charset'];
      $this->phpMailer->Host = Constants::$emailSettings['smtp_host'];
      $this->phpMailer->From = Constants::$emailSettings['from_email'];
      $this->phpMailer->Sender = Constants::$emailSettings['return_path_email'];
      $this->phpMailer->FromName = Constants::$emailSettings['from_name'];
      $this->phpMailer->AddCustomHeader( 'Auto-Submitted:auto-generated' );
      $this->phpMailer->AddCustomHeader( 'X-Auto-Response-Suppress: All' );

      # Setup new line and encoding to avoid extra new lines with some smtp gateways like sendgrid.net
      $this->phpMailer->LE         = "\r\n";
      $this->phpMailer->Encoding   = 'quoted-printable';

      try {
         $this->phpMailer->AddAddress( $t_recipient );
      }
      catch ( phpmailerException $e ) {
         self::$logger->error( 'Message could not be sent - ' . $this->phpMailer->ErrorInfo );
         $t_success = false;
         $this->phpMailer->ClearAllRecipients();
         $this->phpMailer->ClearAttachments();
         $this->phpMailer->ClearReplyTos();
         $this->phpMailer->ClearCustomHeaders();
         return $t_success;
      }

      $this->phpMailer->Subject = $t_subject;
      $this->phpMailer->Body = make_lf_crlf( "\n" . $t_message );

      if( isset( $t_email_data->metadata['headers'] ) && is_array( $t_email_data->metadata['headers'] ) ) {
         foreach( $t_email_data->metadata['headers'] as $t_key => $t_value ) {
            switch( $t_key ) {
               case 'Message-ID':
                  # Note: hostname can never be blank here as we set metadata['hostname']
                  # in email_store() where mail gets queued.
                  if( !strchr( $t_value, '@' ) && !is_blank( $this->phpMailer->Hostname ) ) {
                     $t_value = $t_value . '@' . $this->phpMailer->Hostname;
                  }
                  $this->phpMailer->set( 'MessageID', '<' . $t_value . '>' );
                  break;
               case 'In-Reply-To':
                  $this->phpMailer->AddCustomHeader( $t_key . ': <' . $t_value . '@' . $this->phpMailer->Hostname . '>' );
                  break;
               default:
                  $this->phpMailer->AddCustomHeader( $t_key . ': ' . $t_value );
                  break;
            }
         }
      }

      try {
         $t_success = $this->phpMailer->Send();
         if( $t_success ) {
            $t_success = true;

            if( $t_email_data->email_id > 0 ) {
               email_queue_delete( $t_email_data->email_id );
            }
         } else {
            # We should never get here, as an exception is thrown after failures
            self::$logger->error( 'Message could not be sent - ' . $this->phpMailer->ErrorInfo );
            $t_success = false;
         }
      }
      catch ( phpmailerException $e ) {
         self::$logger->error( 'Message could not be sent - ' . $this->phpMailer->ErrorInfo );
         $t_success = false;
      }

      $this->phpMailer->ClearAllRecipients();
      $this->phpMailer->ClearAttachments();
      $this->phpMailer->ClearReplyTos();
      $this->phpMailer->ClearCustomHeaders();

      return $t_success;
   }

   /**
    * closes opened kept alive SMTP connection (if it was opened)
    *
    * @author MantisBT
    * @return void
    */
   private function smtpClose() {

      if( !is_null( $this->phpMailer ) ) {
         if( $this->phpMailer->smtp->Connected() ) {
            $this->phpMailer->smtp->Quit();
            $this->phpMailer->smtp->Close();
         }
         $this->phpMailer = null;
      }
   }

}


