
<?php 
set_time_limit(3000); 


download_files("email");

function download_files($email_user){
    $hostname = '{mail.server.cl:993/imap/ssl}INBOX';
    $username = 'email@analizar.cl'; 
    $password = 'password';

    $inbox = imap_open($hostname,$username,$password) or die('Sin conexion al email: ' . imap_last_error());

    $emails = imap_search($inbox, 'FROM '.$email_user);

    /* si existe el email lo busca */
    if($emails) {

        $count = 1;

        /* put the newest emails on top */
        rsort($emails);

        /* todos los emial ... */
        foreach($emails as $email_number) 
        {

            /* toma la informacion especifica */
            $overview = imap_fetch_overview($inbox,$email_number,0);

            $message = imap_fetchbody($inbox,$email_number,2);

            /* toma el emial structura */
            $structure = imap_fetchstructure($inbox, $email_number);

            $attachments = array();

            /* si toma todo los datos... */
            if(isset($structure->parts) && count($structure->parts)) 
            {
                for($i = 0; $i < count($structure->parts); $i++) 
                {
                    $attachments[$i] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );

                    if($structure->parts[$i]->ifdparameters) 
                    {
                        foreach($structure->parts[$i]->dparameters as $object) 
                        {
                            if(strtolower($object->attribute) == 'filename') 
                            {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['filename'] = $object->value;
                            }
                        }
                    }

                    if($structure->parts[$i]->ifparameters) 
                    {
                        foreach($structure->parts[$i]->parameters as $object) 
                        {
                            if(strtolower($object->attribute) == 'name') 
                            {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['name'] = $object->value;
                            }
                        }
                    }

                    if($attachments[$i]['is_attachment']) 
                    {
                        $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);

                        /* 3 = BASE64 encoding */
                        if($structure->parts[$i]->encoding == 3) 
                        { 
                            $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                        }
                        /* 4 = QUOTED-PRINTABLE encoding */
                        elseif($structure->parts[$i]->encoding == 4) 
                        { 
                            $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                        }
                    }
                }
            }

            /* itera los arhivos necontreado */
            foreach($attachments as $attachment)
            {
                if($attachment['is_attachment'] == 1)
                {
                    $filename = $attachment['name'];
                    if(empty($filename)) $filename = $attachment['filename'];

                    if(empty($filename)) $filename = time() . ".dat";
                    $folder = "attachment";
                    if(!is_dir($folder))
                    {
                        mkdir($folder);
                    }
                    $fp = fopen("./". $folder ."/". $email_number . "-" . $filename, "w+");
                    fwrite($fp, $attachment['attachment']);
                    fclose($fp);
                }
            }
        }
    } 

    /* cierra la conexion */
    imap_close($inbox);

    echo "all attachment Downloaded";

}


?>