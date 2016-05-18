<?php
class MailClass
{
    // Init necessary properties
    private $requestObj;

    private $mailbox;
    private $letters;
    private $form_data = array();

    // Construct, set Request object using DI pattetn
    public function __construct($requestObj) {
        $this->requestObj = $requestObj;
    }

    // Open mailbox
    private function open_mailbox() {
        // Init IMAP connection
        $mailbox = @imap_open('{' . IMAP_URL . ':' . IMAP_PORT . '/imap/ssl/novalidate-cert}INBOX', IMAP_LOGIN, IMAP_PASSWORD);
        // Check status of connection
        if (!@$mailbox) {
            throw new Exception('Can\'t open mailbox, seems like problems with IMAP connection');
        }

        // This returns mail ID's from "notifications@unbounce.com"
        $letters = @array_reverse(@imap_search($mailbox, 'ALL FROM "SAMPLE"'));
        // Check success of mail list reveiving, if unsuccessful - then throw exception, else fill class properties
        if (@empty($letters)) {
            throw new Exception('Can\'t receive list of letters, maybe maibox is empty');
        } else {
            $this->mailbox = $mailbox;
            $this->letters = $letters;
        }
    }

    // Read letters
    private function read_letters() {
        $letter_number = 0;
        foreach ($this->letters as $letter_id) {
            // Receive body of letter
            $letter_body = imap_body($this->mailbox, $letter_id);

            // Parse letter content into separate variables
            $this->parse_letters($letter_body, $letter_number);

            // Delete parsed letter
            imap_delete($this->mailbox, $letter_id);

            $letter_number++;
        }

        // Clear mailbox from letters marked for delete
        imap_expunge($this->mailbox);

        // Close mail box
        imap_close($this->mailbox);
    }

    // Parse letters content into separate variables
    private function parse_letters($letter_body, $letter_number) {
        // Set parsing pattern
        $pattern = '<td style="font-size: 12px; padding-left: 8px; padding-right: 8px; text-align: left; width: 70%" align="left">(.*)</td>';
        // Parse letter body using above pattern
        preg_match_all('|' . $pattern . '|isU', $letter_body, $form_items);

        // Fill "form_data" array with parsed data
        foreach ($form_items as $form_item) {
            $this->form_data[$letter_number]['first_name'] = $form_item[3];
            $this->form_data[$letter_number]['last_name'] = $form_item[4];
            $this->form_data[$letter_number]['email'] = $form_item[5];
            $this->form_data[$letter_number]['phone'] = $form_item[6];
            $this->form_data[$letter_number]['city'] = $form_item[7];
            $this->form_data[$letter_number]['ip'] = $form_item[8];
        }
    }

    // Set up connection data
    private function set_connection_data($form_data_count) {
        $connection_data['show_headers'] = true;

        $url_params =
            http_build_query(
                array(
                    'potText' => '',
                    'formId' => 'Static Data',
                    'formName' => 'Static Data',
                    'formType' => 'Static Data',
                    'firstname' => 'Static Data',
                    'lastname' => 'Static Data',
                    'email' => 'Static Data',
                    'countryCallingCode' => '+1',
                    'phone' => 'Static Data',
                    'company' => 'Static Data',
                    'referralfirstname' => $this->form_data[$form_data_count]['first_name'],
                    'referrallastname' => $this->form_data[$form_data_count]['last_name'],
                    'referralemail' => $this->form_data[$form_data_count]['email'],
                    'referralphone' => $this->form_data[$form_data_count]['phone'],
                    'referralcity' => $this->form_data[$form_data_count]['city'],
                    'page' => 'Static Data',
                    'ip' => $this->form_data[$form_data_count]['ip'],
                    'referrer' => 'Static Data'
                )
            );

        $connection_data['url'] = SAMPLE_HANDLER . '?' . $url_params;
        $connection_data['referrer'] = SAMPLE_REFERRER;
        $connection_data['timeout'] = SAMPLE_TIMEOUT;

        return $connection_data;
    }

    public function run_application() {
        // Try to open mailbox and receive list of letters
        try {
            $this->open_mailbox();
        } catch (Exception $e) {
            echo($e->getMessage());
            return false;
        }

        // Try to recieve data from letters
        $this->read_letters();

        // Go in cycle through parsed data and send it to SAMPLE handler
        $form_data_count = 0;
        foreach ($this->form_data as $form_variable) {
            // Prepare connection data array
            $connection_data = $this->set_connection_data($form_data_count);

            // Output parsed values
            echo('Letter <b>#' . $form_data_count . '</b> is parsed, extracted values:<br /><br />');

            echo('<b>First Name</b>: ' . $form_variable['first_name'] . '<br />');
            echo('<b>Last Name</b>: ' . $form_variable['last_name'] . '<br />');
            echo('<b>E-Mail</b>: ' . $form_variable['email'] . '<br />');
            echo('<b>Phone</b>: ' . $form_variable['phone'] . '<br />');
            echo('<b>City</b>: ' . $form_variable['city'] . '<br />');
            echo('<b>IP</b>: ' . $form_variable['ip'] . '<br /><br />');

            // Send data to SAMPLE handler and output result
            if (@strstr($this->requestObj->curl_connect($connection_data), 'thank-you')) {
                echo('<b>Form is successfully submitted!</b><br /><br />');
            } else {
                echo('<b>Something wrong with form sending!</b><br /><br />');
            }

            $form_data_count++;
        }
    }
}