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
 * Description of Email
 *
 * @author MantisBT, CodevTT
 */
class Email {
   private static $logger;

   private $phpMailer;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct() {

      try {
         self::$phpMailer = new PHPMailer( true );

      } catch (Exception $e) {
         self::$logger->error('Email constructor: '.$e->getMessage());
         throw $e;
      }
   }

    function __destruct() {
      self::$logger->error('Email destructor.');

      if( Constants::PHPMAILER_METHOD_SMTP == Constants::$emailSettings['phpMailer_method']) {
         self::email_smtp_close();
      }
    }


   /**
    * This function sends an email message based on the supplied email data.
    *
    * @author MantisBT
    * @param EmailData $p_email_data Email Data object representing the email to send.
    * @return boolean
    */
   public function email_send( EmailData $p_email_data ) {

      $t_email_data = $p_email_data;

      $t_recipient = trim( $t_email_data->email );
      $t_subject = string_email( trim( $t_email_data->subject ) );
      $t_message = string_email_links( trim( $t_email_data->body ) );

      if( isset( $t_email_data->metadata['hostname'] ) ) {
         self::$phpMailer->Hostname = $t_email_data->metadata['hostname'];
      }

      # Select the method to send mail
      switch( Constants::$emailSettings['phpMailer_method'] ) {
         case Constants::PHPMAILER_METHOD_MAIL:
            self::$phpMailer->IsMail();
            break;

         case Constants::PHPMAILER_METHOD_SENDMAIL:
            self::$phpMailer->IsSendmail();
            break;

         case Constants::PHPMAILER_METHOD_SMTP:
            self::$phpMailer->IsSMTP();

            # SMTP collection is always kept alive
            self::$phpMailer->SMTPKeepAlive = true;

            if( !Toos::is_blank( Constants::$emailSettings['smtp_username'] ) ) {
               # Use SMTP Authentication
               self::$phpMailer->SMTPAuth = true;
               self::$phpMailer->Username = Constants::$emailSettings['smtp_username'];
               self::$phpMailer->Password = Constants::$emailSettings['smtp_password'];
            }

            if( !Toos::is_blank( Constants::$emailSettings['smtp_connection_mode'] ) ) {
               self::$phpMailer->SMTPSecure = Constants::$emailSettings['smtp_connection_mode'];
            }

            self::$phpMailer->Port = Constants::$emailSettings['smtp_port'];

            break;
         default:
            self::$logger->error( 'Unknown phpMailer_method - ' . Constants::$emailSettings['phpMailer_method'] );
      }

      self::$phpMailer->IsHTML( false );              # set email format to plain text
      self::$phpMailer->WordWrap = 80;              # set word wrap to 80 characters
      self::$phpMailer->Priority = $t_email_data->metadata['priority'];  # Urgent = 1, Not Urgent = 5, Disable = 0
      self::$phpMailer->CharSet = $t_email_data->metadata['charset'];
      self::$phpMailer->Host = Constants::$emailSettings['smtp_host'];
      self::$phpMailer->From = Constants::$emailSettings['from_email'];
      self::$phpMailer->Sender = Constants::$emailSettings['return_path_email'];
      self::$phpMailer->FromName = Constants::$emailSettings['from_name'];
      self::$phpMailer->AddCustomHeader( 'Auto-Submitted:auto-generated' );
      self::$phpMailer->AddCustomHeader( 'X-Auto-Response-Suppress: All' );

      # Setup new line and encoding to avoid extra new lines with some smtp gateways like sendgrid.net
      self::$phpMailer->LE         = "\r\n";
      self::$phpMailer->Encoding   = 'quoted-printable';

      try {
         self::$phpMailer->AddAddress( $t_recipient );
      }
      catch ( phpmailerException $e ) {
         self::$logger->error( 'Message could not be sent - ' . self::$phpMailer->ErrorInfo );
         $t_success = false;
         self::$phpMailer->ClearAllRecipients();
         self::$phpMailer->ClearAttachments();
         self::$phpMailer->ClearReplyTos();
         self::$phpMailer->ClearCustomHeaders();
         return $t_success;
      }

      self::$phpMailer->Subject = $t_subject;
      self::$phpMailer->Body = make_lf_crlf( "\n" . $t_message );

      if( isset( $t_email_data->metadata['headers'] ) && is_array( $t_email_data->metadata['headers'] ) ) {
         foreach( $t_email_data->metadata['headers'] as $t_key => $t_value ) {
            switch( $t_key ) {
               case 'Message-ID':
                  # Note: hostname can never be blank here as we set metadata['hostname']
                  # in email_store() where mail gets queued.
                  if( !strchr( $t_value, '@' ) && !is_blank( self::$phpMailer->Hostname ) ) {
                     $t_value = $t_value . '@' . self::$phpMailer->Hostname;
                  }
                  self::$phpMailer->set( 'MessageID', '<' . $t_value . '>' );
                  break;
               case 'In-Reply-To':
                  self::$phpMailer->AddCustomHeader( $t_key . ': <' . $t_value . '@' . self::$phpMailer->Hostname . '>' );
                  break;
               default:
                  self::$phpMailer->AddCustomHeader( $t_key . ': ' . $t_value );
                  break;
            }
         }
      }

      try {
         $t_success = self::$phpMailer->Send();
         if( $t_success ) {
            $t_success = true;

            if( $t_email_data->email_id > 0 ) {
               email_queue_delete( $t_email_data->email_id );
            }
         } else {
            # We should never get here, as an exception is thrown after failures
            self::$logger->error( 'Message could not be sent - ' . self::$phpMailer->ErrorInfo );
            $t_success = false;
         }
      }
      catch ( phpmailerException $e ) {
         self::$logger->error( 'Message could not be sent - ' . self::$phpMailer->ErrorInfo );
         $t_success = false;
      }

      self::$phpMailer->ClearAllRecipients();
      self::$phpMailer->ClearAttachments();
      self::$phpMailer->ClearReplyTos();
      self::$phpMailer->ClearCustomHeaders();

      return $t_success;
   }

   /**
    * closes opened kept alive SMTP connection (if it was opened)
    *
    * @author MantisBT
    * @return void
    */
   public function email_smtp_close() {

      if( !is_null( self::$phpMailer ) ) {
         if( self::$phpMailer->smtp->Connected() ) {
            self::$phpMailer->smtp->Quit();
            self::$phpMailer->smtp->Close();
         }
         self::$phpMailer = null;
      }
   }

}

// Initialize static variables
Email::staticInit();

