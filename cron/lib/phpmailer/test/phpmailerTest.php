<?php
/**
 * PHPMailer - PHP email transport unit tests
 * Requires PHPUnit 3.3 or later. Install like this:
 *   pear install "channel://pear.phpunit.de/PHPUnit"
 * Then run the tests like this:
 *   phpunit phpmailerTest
 *
 * PHP version 5.0.0
 *
 * @package PHPMailer
 * @author Andy Prevost
 * @author Marcus Bointon <phpmailer@synchromedia.co.uk>
 * @copyright 2004 - 2009 Andy Prevost
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require 'PHPUnit/Autoload.php';
require '../PHPMailerAutoload.php';

/**
 * PHPMailer - PHP email transport unit test class
 * Performs authentication tests
 */
class PHPMailerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Holds the default phpmailer instance.
     * @private
     * @type PHPMailer
     */
    public $Mail;

    /**
     * Holds the SMTP mail host.
     * @public
     * @type string
     */
    public $Host = '';

    /**
     * Holds the change log.
     * @private
     * @type string[]
     */
    public $ChangeLog = array();

    /**
     * Holds the note log.
     * @private
     * @type string[]
     */
    public $NoteLog = array();

    /**
     * Default include path
     * @type string
     */
    public $INCLUDE_DIR = '../';

    /**
     * PIDs of any processes we need to kill
     * @type array
     * @access private
     */
    private $pids = array();

    /**
     * Run before each test is started.
     */
    public function setUp()
    {
        if (file_exists('./testbootstrap.php')) {
            include './testbootstrap.php'; //Overrides go in here
        }
        $this->Mail = new PHPMailer;
        $this->Mail->Priority = 3;
        $this->Mail->Encoding = '8bit';
        $this->Mail->CharSet = 'iso-8859-1';
        if (array_key_exists('mail_from', $_REQUEST)) {
            $this->Mail->From = $_REQUEST['mail_from'];
        } else {
            $this->Mail->From = 'unit_test@phpmailer.example.com';
        }
        $this->Mail->FromName = 'Unit Tester';
        $this->Mail->Sender = '';
        $this->Mail->Subject = 'Unit Test';
        $this->Mail->Body = '';
        $this->Mail->AltBody = '';
        $this->Mail->WordWrap = 0;
        if (array_key_exists('mail_host', $_REQUEST)) {
            $this->Mail->Host = $_REQUEST['mail_host'];
        } else {
            $this->Mail->Host = 'mail.example.com';
        }
        if (array_key_exists('mail_port', $_REQUEST)) {
            $this->Mail->Port = $_REQUEST['mail_port'];
        } else {
            $this->Mail->Port = 25;
        }
        $this->Mail->Helo = 'localhost.localdomain';
        $this->Mail->SMTPAuth = false;
        $this->Mail->Username = '';
        $this->Mail->Password = '';
        $this->Mail->PluginDir = $this->INCLUDE_DIR;
        $this->Mail->addReplyTo('no_reply@phpmailer.example.com', 'Reply Guy');
        $this->Mail->Sender = 'unit_test@phpmailer.example.com';
        if (strlen($this->Mail->Host) > 0) {
            $this->Mail->Mailer = 'smtp';
        } else {
            $this->Mail->Mailer = 'mail';
            $this->Mail->Sender = 'unit_test@phpmailer.example.com';
        }
        if (array_key_exists('mail_to', $_REQUEST)) {
            $this->setAddress($_REQUEST['mail_to'], 'Test User', 'to');
        }
        if (array_key_exists('mail_cc', $_REQUEST) and strlen($_REQUEST['mail_cc']) > 0) {
            $this->setAddress($_REQUEST['mail_cc'], 'Carbon User', 'cc');
        }
    }

    /**
     * Run after each test is completed.
     */
    public function tearDown()
    {
        // Clean global variables
        $this->Mail = null;
        $this->ChangeLog = array();
        $this->NoteLog = array();

        foreach ($this->pids as $pid) {
            $p = escapeshellarg($pid);
            shell_exec("ps $p && kill -TERM $p");
        }
    }


    /**
     * Build the body of the message in the appropriate format.
     *
     * @private
     * @return void
     */
    public function buildBody()
    {
        $this->checkChanges();

        // Determine line endings for message
        if ($this->Mail->ContentType == 'text/html' || strlen($this->Mail->AltBody) > 0) {
            $eol = '<br/>';
            $bullet = '<li>';
            $bullet_start = '<ul>';
            $bullet_end = '</ul>';
        } else {
            $eol = "\n";
            $bullet = ' - ';
            $bullet_start = '';
            $bullet_end = '';
        }

        $ReportBody = '';

        $ReportBody .= '---------------------' . $eol;
        $ReportBody .= 'Unit Test Information' . $eol;
        $ReportBody .= '---------------------' . $eol;
        $ReportBody .= 'phpmailer version: ' . $this->Mail->Version . $eol;
        $ReportBody .= 'Content Type: ' . $this->Mail->ContentType . $eol;

        if (strlen($this->Mail->Host) > 0) {
            $ReportBody .= 'Host: ' . $this->Mail->Host . $eol;
        }

        // If attachments then create an attachment list
        $attachments = $this->Mail->getAttachments();
        if (count($attachments) > 0) {
            $ReportBody .= 'Attachments:' . $eol;
            $ReportBody .= $bullet_start;
            foreach ($attachments as $attachment) {
                $ReportBody .= $bullet . 'Name: ' . $attachment[1] . ', ';
                $ReportBody .= 'Encoding: ' . $attachment[3] . ', ';
                $ReportBody .= 'Type: ' . $attachment[4] . $eol;
            }
            $ReportBody .= $bullet_end . $eol;
        }

        // If there are changes then list them
        if (count($this->ChangeLog) > 0) {
            $ReportBody .= 'Changes' . $eol;
            $ReportBody .= '-------' . $eol;

            $ReportBody .= $bullet_start;
            for ($i = 0; $i < count($this->ChangeLog); $i++) {
                $ReportBody .= $bullet . $this->ChangeLog[$i][0] . ' was changed to [' .
                    $this->ChangeLog[$i][1] . ']' . $eol;
            }
            $ReportBody .= $bullet_end . $eol . $eol;
        }

        // If there are notes then list them
        if (count($this->NoteLog) > 0) {
            $ReportBody .= 'Notes' . $eol;
            $ReportBody .= '-----' . $eol;

            $ReportBody .= $bullet_start;
            for ($i = 0; $i < count($this->NoteLog); $i++) {
                $ReportBody .= $bullet . $this->NoteLog[$i] . $eol;
            }
            $ReportBody .= $bullet_end;
        }

        // Re-attach the original body
        $this->Mail->Body .= $eol . $eol . $ReportBody;
    }

    /**
     * Check which default settings have been changed for the report.
     * @private
     * @return void
     */
    public function checkChanges()
    {
        if ($this->Mail->Priority != 3) {
            $this->addChange('Priority', $this->Mail->Priority);
        }
        if ($this->Mail->Encoding != '8bit') {
            $this->addChange('Encoding', $this->Mail->Encoding);
        }
        if ($this->Mail->CharSet != 'iso-8859-1') {
            $this->addChange('CharSet', $this->Mail->CharSet);
        }
        if ($this->Mail->Sender != '') {
            $this->addChange('Sender', $this->Mail->Sender);
        }
        if ($this->Mail->WordWrap != 0) {
            $this->addChange('WordWrap', $this->Mail->WordWrap);
        }
        if ($this->Mail->Mailer != 'mail') {
            $this->addChange('Mailer', $this->Mail->Mailer);
        }
        if ($this->Mail->Port != 25) {
            $this->addChange('Port', $this->Mail->Port);
        }
        if ($this->Mail->Helo != 'localhost.localdomain') {
            $this->addChange('Helo', $this->Mail->Helo);
        }
        if ($this->Mail->SMTPAuth) {
            $this->addChange('SMTPAuth', 'true');
        }
    }

    /**
     * Add a changelog entry.
     * @access private
     * @param string $sName
     * @param string $sNewValue
     * @return void
     */
    public function addChange($sName, $sNewValue)
    {
        $this->ChangeLog[] = array($sName, $sNewValue);
    }

    /**
     * Adds a simple note to the message.
     * @public
     * @param string $sValue
     * @return void
     */
    public function addNote($sValue)
    {
        $this->NoteLog[] = $sValue;
    }

    /**
     * Adds all of the addresses
     * @access public
     * @param string $sAddress
     * @param string $sName
     * @param string $sType
     * @return boolean
     */
    public function setAddress($sAddress, $sName = '', $sType = 'to')
    {
        switch ($sType) {
            case 'to':
                return $this->Mail->addAddress($sAddress, $sName);
            case 'cc':
                return $this->Mail->addCC($sAddress, $sName);
            case 'bcc':
                return $this->Mail->addBCC($sAddress, $sName);
        }
        return false;
    }

    /**
     * Test CRAM-MD5 authentication
     * Needs a connection to a server that supports this auth mechanism, so commented out by default
     */
    public function testAuthCRAMMD5()
    {
        $this->Mail->Host = 'hostname';
        $this->Mail->Port = 587;
        $this->Mail->SMTPAuth = true;
        $this->Mail->SMTPSecure = 'tls';
        $this->Mail->AuthType = 'CRAM-MD5';
        $this->Mail->Username = 'username';
        $this->Mail->Password = 'password';
        $this->Mail->Body = 'Test body';
        $this->Mail->Subject .= ': Auth CRAM-MD5';
        $this->Mail->From = 'from@example.com';
        $this->Mail->Sender = 'from@example.com';
        $this->Mail->clearAllRecipients();
        $this->Mail->addAddress('user@example.com');
        //$this->assertTrue($this->mail->send(), $this->mail->ErrorInfo);
    }

    /**
     * Test email address validation
     * Test addresses obtained from http://isemail.info
     * Some failing cases commented out that are apparently up for debate!
     */
    public function testValidate()
    {
        $validaddresses = array(
            'first@iana.org',
            'first.last@iana.org',
            '1234567890123456789012345678901234567890123456789012345678901234@iana.org',
            '"first\"last"@iana.org',
            '"first@last"@iana.org',
            '"first\last"@iana.org',
            'first.last@[12.34.56.78]',
            'first.last@[IPv6:::12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333::4444:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:12.34.56.78]',
            'first.last@[IPv6:::1111:2222:3333:4444:5555:6666]',
            'first.last@[IPv6:1111:2222:3333::4444:5555:6666]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666::]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:8888]',
            'first.last@x23456789012345678901234567890123456789012345678901234567890123.iana.org',
            'first.last@3com.com',
            'first.last@123.iana.org',
            '"first\last"@iana.org',
            'first.last@[IPv6:1111:2222:3333::4444:5555:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333::4444:5555:6666:7777]',
            'first.last@example.123',
            'first.last@com',
            '"Abc\@def"@iana.org',
            '"Fred\ Bloggs"@iana.org',
            '"Joe.\Blow"@iana.org',
            '"Abc@def"@iana.org',
            '"Fred Bloggs"@iana.org',
            'user+mailbox@iana.org',
            'customer/department=shipping@iana.org',
            '$A12345@iana.org',
            '!def!xyz%abc@iana.org',
            '_somename@iana.org',
            'dclo@us.ibm.com',
            'peter.piper@iana.org',
            '"Doug \"Ace\" L."@iana.org',
            'test@iana.org',
            'TEST@iana.org',
            '1234567890@iana.org',
            'test+test@iana.org',
            'test-test@iana.org',
            't*est@iana.org',
            '+1~1+@iana.org',
            '{_test_}@iana.org',
            '"[[ test ]]"@iana.org',
            'test.test@iana.org',
            '"test.test"@iana.org',
            'test."test"@iana.org',
            '"test@test"@iana.org',
            'test@123.123.123.x123',
            'test@123.123.123.123',
            'test@[123.123.123.123]',
            'test@example.iana.org',
            'test@example.example.iana.org',
            '"test\test"@iana.org',
            'test@example',
            '"test\blah"@iana.org',
            '"test\blah"@iana.org',
            '"test\"blah"@iana.org',
            'customer/department@iana.org',
            '_Yosemite.Sam@iana.org',
            '~@iana.org',
            '"Austin@Powers"@iana.org',
            'Ima.Fool@iana.org',
            '"Ima.Fool"@iana.org',
            '"Ima Fool"@iana.org',
            '"first"."last"@iana.org',
            '"first".middle."last"@iana.org',
            '"first".last@iana.org',
            'first."last"@iana.org',
            '"first"."middle"."last"@iana.org',
            '"first.middle"."last"@iana.org',
            '"first.middle.last"@iana.org',
            '"first..last"@iana.org',
            '"first\"last"@iana.org',
            'first."mid\dle"."last"@iana.org',
            '"test blah"@iana.org',
            '(foo)cal(bar)@(baz)iamcal.com(quux)',
            'cal@iamcal(woo).(yay)com',
            'cal(woo(yay)hoopla)@iamcal.com',
            'cal(foo\@bar)@iamcal.com',
            'cal(foo\)bar)@iamcal.com',
            'first().last@iana.org',
            'pete(his account)@silly.test(his host)',
            'c@(Chris\'s host.)public.example',
            'jdoe@machine(comment). example',
            '1234 @ local(blah) .machine .example',
            'first(abc.def).last@iana.org',
            'first(a"bc.def).last@iana.org',
            'first.(")middle.last(")@iana.org',
            'first(abc\(def)@iana.org',
            'first.last@x(1234567890123456789012345678901234567890123456789012345678901234567890).com',
            'a(a(b(c)d(e(f))g)h(i)j)@iana.org',
            'name.lastname@domain.com',
            'a@b',
            'a@bar.com',
            'aaa@[123.123.123.123]',
            'a@bar',
            'a-b@bar.com',
            '+@b.c',
            '+@b.com',
            'a@b.co-foo.uk',
            '"hello my name is"@stutter.com',
            '"Test \"Fail\" Ing"@iana.org',
            'valid@about.museum',
            'shaitan@my-domain.thisisminekthx',
            'foobar@192.168.0.1',
            '"Joe\Blow"@iana.org',
            'HM2Kinsists@(that comments are allowed)this.is.ok',
            'user%uucp!path@berkeley.edu',
            'first.last @iana.org',
            'cdburgess+!#$%&\'*-/=?+_{}|~test@gmail.com',
            'first.last@[IPv6:::a2:a3:a4:b1:b2:b3:b4]',
            'first.last@[IPv6:a1:a2:a3:a4:b1:b2:b3::]',
            'first.last@[IPv6:::]',
            'first.last@[IPv6:::b4]',
            'first.last@[IPv6:::b3:b4]',
            'first.last@[IPv6:a1::b4]',
            'first.last@[IPv6:a1::]',
            'first.last@[IPv6:a1:a2::]',
            'first.last@[IPv6:0123:4567:89ab:cdef::]',
            'first.last@[IPv6:0123:4567:89ab:CDEF::]',
            'first.last@[IPv6:::a3:a4:b1:ffff:11.22.33.44]',
            'first.last@[IPv6:::a2:a3:a4:b1:ffff:11.22.33.44]',
            'first.last@[IPv6:a1:a2:a3:a4::11.22.33.44]',
            'first.last@[IPv6:a1:a2:a3:a4:b1::11.22.33.44]',
            'first.last@[IPv6:a1::11.22.33.44]',
            'first.last@[IPv6:a1:a2::11.22.33.44]',
            'first.last@[IPv6:0123:4567:89ab:cdef::11.22.33.44]',
            'first.last@[IPv6:0123:4567:89ab:CDEF::11.22.33.44]',
            'first.last@[IPv6:a1::b2:11.22.33.44]',
            'test@test.com',
            'test@xn--example.com',
            'test@example.com'
        );
        $invalidaddresses = array(
            'first.last@sub.do,com',
            'first\@last@iana.org',
            '123456789012345678901234567890123456789012345678901234567890' .
                '@12345678901234567890123456789012345678901234 [...]',
            'first.last',
            '12345678901234567890123456789012345678901234567890123456789012345@iana.org',
            '.first.last@iana.org',
            'first.last.@iana.org',
            'first..last@iana.org',
            '"first"last"@iana.org',
            '"""@iana.org',
            '"\"@iana.org',
            //'""@iana.org',
            'first\@last@iana.org',
            'first.last@',
            'x@x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.x23456789.' .
                'x23456789.x23456789.x23456789.x23 [...]',
            'first.last@[.12.34.56.78]',
            'first.last@[12.34.56.789]',
            'first.last@[::12.34.56.78]',
            'first.last@[IPv5:::12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:12.34.56.78]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777]',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:7777:8888:9999]',
            'first.last@[IPv6:1111:2222::3333::4444:5555:6666]',
            'first.last@[IPv6:1111:2222:333x::4444:5555]',
            'first.last@[IPv6:1111:2222:33333::4444:5555]',
            'first.last@-xample.com',
            'first.last@exampl-.com',
            'first.last@x234567890123456789012345678901234567890123456789012345678901234.iana.org',
            'abc\@def@iana.org',
            'abc\@iana.org',
            'Doug\ \"Ace\"\ Lovell@iana.org',
            'abc@def@iana.org',
            'abc\@def@iana.org',
            'abc\@iana.org',
            '@iana.org',
            'doug@',
            '"qu@iana.org',
            'ote"@iana.org',
            '.dot@iana.org',
            'dot.@iana.org',
            'two..dot@iana.org',
            '"Doug "Ace" L."@iana.org',
            'Doug\ \"Ace\"\ L\.@iana.org',
            'hello world@iana.org',
            //'helloworld@iana .org',
            'gatsby@f.sc.ot.t.f.i.tzg.era.l.d.',
            'test.iana.org',
            'test.@iana.org',
            'test..test@iana.org',
            '.test@iana.org',
            'test@test@iana.org',
            'test@@iana.org',
            '-- test --@iana.org',
            '[test]@iana.org',
            '"test"test"@iana.org',
            '()[]\;:,><@iana.org',
            'test@.',
            'test@example.',
            'test@.org',
            'test@12345678901234567890123456789012345678901234567890123456789012345678901234567890' .
                '12345678901234567890 [...]',
            'test@[123.123.123.123',
            'test@123.123.123.123]',
            'NotAnEmail',
            '@NotAnEmail',
            '"test"blah"@iana.org',
            '.wooly@iana.org',
            'wo..oly@iana.org',
            'pootietang.@iana.org',
            '.@iana.org',
            'Ima Fool@iana.org',
            'phil.h\@\@ck@haacked.com',
            'foo@[\1.2.3.4]',
            //'first."".last@iana.org',
            'first\last@iana.org',
            'Abc\@def@iana.org',
            'Fred\ Bloggs@iana.org',
            'Joe.\Blow@iana.org',
            'first.last@[IPv6:1111:2222:3333:4444:5555:6666:12.34.567.89]',
            '{^c\@**Dog^}@cartoon.com',
            //'"foo"(yay)@(hoopla)[1.2.3.4]',
            'cal(foo(bar)@iamcal.com',
            'cal(foo)bar)@iamcal.com',
            'cal(foo\)@iamcal.com',
            'first(12345678901234567890123456789012345678901234567890)last@(1234567890123456789' .
                '01234567890123456789012 [...]',
            'first(middle)last@iana.org',
            'first(abc("def".ghi).mno)middle(abc("def".ghi).mno).last@(abc("def".ghi).mno)example' .
                '(abc("def".ghi).mno). [...]',
            'a(a(b(c)d(e(f))g)(h(i)j)@iana.org',
            '.@',
            '@bar.com',
            '@@bar.com',
            'aaa.com',
            'aaa@.com',
            'aaa@.123',
            'aaa@[123.123.123.123]a',
            'aaa@[123.123.123.333]',
            'a@bar.com.',
            'a@-b.com',
            'a@b-.com',
            '-@..com',
            '-@a..com',
            'invalid@about.museum-',
            'test@...........com',
            '"Unicode NULL' . chr(0) . '"@char.com',
            'Unicode NULL' . chr(0) . '@char.com',
            'first.last@[IPv6::]',
            'first.last@[IPv6::::]',
            'first.last@[IPv6::b4]',
            'first.last@[IPv6::::b4]',
            'first.last@[IPv6::b3:b4]',
            'first.last@[IPv6::::b3:b4]',
            'first.last@[IPv6:a1:::b4]',
            'first.last@[IPv6:a1:]',
            'first.last@[IPv6:a1:::]',
            'first.last@[IPv6:a1:a2:]',
            'first.last@[IPv6:a1:a2:::]',
            'first.last@[IPv6::11.22.33.44]',
            'first.last@[IPv6::::11.22.33.44]',
            'first.last@[IPv6:a1:11.22.33.44]',
            'first.last@[IPv6:a1:::11.22.33.44]',
            'first.last@[IPv6:a1:a2:::11.22.33.44]',
            'first.last@[IPv6:0123:4567:89ab:cdef::11.22.33.xx]',
            'first.last@[IPv6:0123:4567:89ab:CDEFF::11.22.33.44]',
            'first.last@[IPv6:a1::a4:b1::b4:11.22.33.44]',
            'first.last@[IPv6:a1::11.22.33]',
            'first.last@[IPv6:a1::11.22.33.44.55]',
            'first.last@[IPv6:a1::b211.22.33.44]',
            'first.last@[IPv6:a1::b2::11.22.33.44]',
            'first.last@[IPv6:a1::b3:]',
            'first.last@[IPv6::a2::b4]',
            'first.last@[IPv6:a1:a2:a3:a4:b1:b2:b3:]',
            'first.last@[IPv6::a2:a3:a4:b1:b2:b3:b4]',
            'first.last@[IPv6:a1:a2:a3:a4::b1:b2:b3:b4]'
        );
        $goodfails = array();
        foreach ($validaddresses as $address) {
            if (!PHPMailer::validateAddress($address)) {
                $goodfails[] = $address;
            }
        }
        $badpasses = array();
        foreach ($invalidaddresses as $address) {
            if (PHPMailer::validateAddress($address)) {
                $badpasses[] = $address;
            }
        }
        $err = '';
        if (count($goodfails) > 0) {
            $err .= "Good addresses that failed validation:\n";
            $err .= implode("\n", $goodfails);
        }
        if (count($badpasses) > 0) {
            if (!empty($err)) {
                $err .= "\n\n";
            }
            $err .= "Bad addresses that passed validation:\n";
            $err .= implode("\n", $badpasses);
        }
        $this->assertEmpty($err, $err);
        //For coverage
        $this->assertTrue(PHPMailer::validateAddress('test@example.com', 'auto'));
        $this->assertFalse(PHPMailer::validateAddress('test@example.com.', 'auto'));
        $this->assertTrue(PHPMailer::validateAddress('test@example.com', 'pcre'));
        $this->assertFalse(PHPMailer::validateAddress('test@example.com.', 'pcre'));
        $this->assertTrue(PHPMailer::validateAddress('test@example.com', 'pcre8'));
        $this->assertFalse(PHPMailer::validateAddress('test@example.com.', 'pcre8'));
        $this->assertTrue(PHPMailer::validateAddress('test@example.com', 'php'));
        $this->assertFalse(PHPMailer::validateAddress('test@example.com.', 'php'));
        $this->assertTrue(PHPMailer::validateAddress('test@example.com', 'noregex'));
        $this->assertFalse(PHPMailer::validateAddress('bad', 'noregex'));
    }

    /**
     * Try a plain message.
     */
    public function testWordWrap()
    {
        $this->Mail->WordWrap = 40;
        $my_body = 'Here is the main body of this message.  It should ' .
            'be quite a few lines.  It should be wrapped at the ' .
            '40 characters.  Make sure that it is.';
        $nBodyLen = strlen($my_body);
        $my_body .= "\n\nThis is the above body length: " . $nBodyLen;

        $this->Mail->Body = $my_body;
        $this->Mail->Subject .= ': Wordwrap';

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Try a plain message.
     */
    public function testLowPriority()
    {
        $this->Mail->Priority = 5;
        $this->Mail->Body = 'Here is the main body.  There should be ' .
            'a reply to address in this message.';
        $this->Mail->Subject .= ': Low Priority';
        $this->Mail->addReplyTo('nobody@nobody.com', 'Nobody (Unit Test)');

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple plain file attachment test.
     */
    public function testMultiplePlainFileAttachment()
    {
        $this->Mail->Body = 'Here is the text body';
        $this->Mail->Subject .= ': Plain + Multiple FileAttachments';

        if (!$this->Mail->addAttachment('../examples/images/phpmailer.png')) {
            $this->assertTrue(false, $this->Mail->ErrorInfo);
            return;
        }

        if (!$this->Mail->addAttachment(__FILE__, 'test.txt')) {
            $this->assertTrue(false, $this->Mail->ErrorInfo);
            return;
        }

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple plain string attachment test.
     */
    public function testPlainStringAttachment()
    {
        $this->Mail->Body = 'Here is the text body';
        $this->Mail->Subject .= ': Plain + StringAttachment';

        $sAttachment = 'These characters are the content of the ' .
            "string attachment.\nThis might be taken from a " .
            'database or some other such thing. ';

        $this->Mail->addStringAttachment($sAttachment, 'string_attach.txt');

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Plain quoted-printable message.
     */
    public function testQuotedPrintable()
    {
        $this->Mail->Body = 'Here is the main body';
        $this->Mail->Subject .= ': Plain + Quoted-printable';
        $this->Mail->Encoding = 'quoted-printable';

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Check that a quoted printable encode and decode results in the same as went in
        $t = file_get_contents(__FILE__); //Use this file as test content
        $this->assertEquals(
            $t,
            quoted_printable_decode($this->Mail->encodeQP($t)),
            'Quoted-Printable encoding round-trip failed'
        );
        $this->assertEquals(
            $this->Mail->encodeQP($t),
            $this->Mail->encodeQPphp($t),
            'Quoted-Printable BC wrapper failed'
        );
    }

    /**
     * Try a plain message.
     */
    public function testHtml()
    {
        $this->Mail->isHTML(true);
        $this->Mail->Subject .= ": HTML only";

        $this->Mail->Body = <<<EOT
<html>
    <head>
        <title>HTML email test</title>
    </head>
    <body>
        <h1>PHPMailer does HTML!</h1>
        <p>This is a <strong>test message</strong> written in HTML.<br>
        Go to <a href="https://github.com/PHPMailer/PHPMailer/">https://github.com/PHPMailer/PHPMailer/</a>
        for new versions of PHPMailer.</p>
        <p>Thank you!</p>
    </body>
</html>
EOT;
        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test simple message builder and html2text converters
     */
    public function testMsgHTML()
    {
        $message = file_get_contents('../examples/contents.html');
        $this->Mail->CharSet = 'utf-8';
        $this->Mail->Body = '';
        $this->Mail->AltBody = '';
        $this->Mail->msgHTML($message, '../examples');
        $this->Mail->Subject .= ': msgHTML';

        $this->assertNotEmpty($this->Mail->Body, 'Body not set by msgHTML');
        $this->assertNotEmpty($this->Mail->AltBody, 'AltBody not set by msgHTML');
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        //Again, using the advanced HTML to text converter
        $this->Mail->AltBody = '';
        $this->Mail->msgHTML($message, '../examples', true);
        $this->Mail->Subject .= ' + html2text advanced';
        $this->assertNotEmpty($this->Mail->AltBody, 'Advanced AltBody not set by msgHTML');

        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple HTML and attachment test
     */
    public function testHTMLAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->Subject .= ': HTML + Attachment';
        $this->Mail->isHTML(true);

        if (!$this->Mail->addAttachment(__FILE__, 'test_attach.txt')) {
            $this->assertTrue(false, $this->Mail->ErrorInfo);
            return;
        }

        //Make sure that trying to attach a nonexistent file fails
        $this->assertFalse($this->Mail->addAttachment(__FILE__ . md5(microtime()), 'nonexistent_file.txt'));

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * An embedded attachment test.
     */
    public function testEmbeddedImage()
    {
        $this->Mail->Body = 'Embedded Image: <img alt="phpmailer" src="cid:my-attach">' .
            'Here is an image!</a>';
        $this->Mail->Subject .= ': Embedded Image';
        $this->Mail->isHTML(true);

        if (!$this->Mail->addEmbeddedImage(
            '../examples/images/phpmailer.png',
            'my-attach',
            'phpmailer.png',
            'base64',
            'image/png'
        )
        ) {
            $this->assertTrue(false, $this->Mail->ErrorInfo);
            return;
        }

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        //For code coverage
        $this->Mail->addEmbeddedImage('thisfiledoesntexist', 'xyz'); //Non-existent file
        $this->Mail->addEmbeddedImage(__FILE__, '123'); //Missing name
    }

    /**
     * An embedded attachment test.
     */
    public function testMultiEmbeddedImage()
    {
        $this->Mail->Body = 'Embedded Image: <img alt="phpmailer" src="cid:my-attach">' .
            'Here is an image!</a>';
        $this->Mail->Subject .= ': Embedded Image + Attachment';
        $this->Mail->isHTML(true);

        if (!$this->Mail->addEmbeddedImage(
            '../examples/images/phpmailer.png',
            'my-attach',
            'phpmailer.png',
            'base64',
            'image/png'
        )
        ) {
            $this->assertTrue(false, $this->Mail->ErrorInfo);
            return;
        }

        if (!$this->Mail->addAttachment(__FILE__, 'test.txt')) {
            $this->assertTrue(false, $this->Mail->ErrorInfo);
            return;
        }

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple multipart/alternative test.
     */
    public function testAltBody()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->AltBody = 'Here is the text body of this message.  ' .
            'It should be quite a few lines.  It should be wrapped at the ' .
            '40 characters.  Make sure that it is.';
        $this->Mail->WordWrap = 40;
        $this->addNote('This is a mulipart alternative email');
        $this->Mail->Subject .= ': AltBody + Word Wrap';

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Simple HTML and attachment test
     */
    public function testAltBodyAttachment()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->AltBody = 'This is the text part of the email.';
        $this->Mail->Subject .= ': AltBody + Attachment';
        $this->Mail->isHTML(true);

        if (!$this->Mail->addAttachment(__FILE__, 'test_attach.txt')) {
            $this->assertTrue(false, $this->Mail->ErrorInfo);
            return;
        }

        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        if (is_writable('.')) {
            file_put_contents('message.txt', $this->Mail->createHeader() . $this->Mail->createBody());
        } else {
            $this->assertTrue(false, 'Could not write local file - check permissions');
        }
    }

    /**
     * iCal event test
     */
    public function testIcal()
    {
        $this->Mail->Body = 'This is the <strong>HTML</strong> part of the email.';
        $this->Mail->AltBody = 'This is the text part of the email.';
        $this->Mail->Subject .= ': iCal';
        $this->Mail->isHTML(true);
        $this->buildBody();
        require_once '../extras/EasyPeasyICS.php';
        $ICS = new EasyPeasyICS("PHPMailer test calendar");
        $ICS->addEvent(
            strtotime('tomorrow 10:00 Europe/Paris'),
            strtotime('tomorrow 11:00 Europe/Paris'),
            'PHPMailer iCal test',
            'A test of PHPMailer iCal support',
            'https://github.com/PHPMailer/PHPMailer'
        );
        $this->Mail->Ical = $ICS->render(false);
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->Body = 'Embedded Image: <img alt="phpmailer" src="cid:my-attach">' .
            'Here is an image!</a>.';
        $this->Mail->AltBody = 'This is the text part of the email.';
        $this->Mail->Subject .= ': iCal + inline';
        $this->Mail->isHTML(true);
        $this->Mail->addEmbeddedImage(
            '../examples/images/phpmailer.png',
            'my-attach',
            'phpmailer.png',
            'base64',
            'image/png'
        );
        $this->buildBody();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending multiple messages with separate connections
     */
    public function testMultipleSend()
    {
        $this->Mail->Body = 'Sending two messages without keepalive';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->Subject = $subject . ': SMTP 1';
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->Subject = $subject . ': SMTP 2';
        $this->Mail->Sender = 'blah@example.com';
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending using SendMail
     */
    public function testSendmailSend()
    {
        $this->Mail->Body = 'Sending via sendmail';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->Subject = $subject . ': sendmail';
        $this->Mail->isSendmail();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending using Qmail
     */
    public function testQmailSend()
    {
        //Only run if we have qmail installed
        if (file_exists('/var/qmail/bin/qmail-inject')) {
            $this->Mail->Body = 'Sending via qmail';
            $this->BuildBody();
            $subject = $this->Mail->Subject;

            $this->Mail->Subject = $subject . ': qmail';
            $this->Mail->IsQmail();
            $this->assertTrue($this->Mail->Send(), $this->Mail->ErrorInfo);
        }
    }

    /**
     * Test sending using PHP mail() function
     */
    public function testMailSend()
    {
        $sendmail = ini_get('sendmail_path');
        if (strpos($sendmail, '/') === false) { //No path in sendmail_path
            ini_set('sendmail_path', '/usr/sbin/sendmail -t -i ');
        }
        $this->Mail->Body = 'Sending via mail()';
        $this->buildBody();

        $this->Mail->Subject = $this->Mail->Subject . ': mail()';
        $this->Mail->isMail();
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test sending an empty body
     */
    public function testEmptyBody()
    {
        $this->buildBody();
        $this->Mail->Body = '';
        $this->Mail->Subject = $this->Mail->Subject . ': Empty Body';
        $this->Mail->isMail();
        $this->Mail->AllowEmpty = true;
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->AllowEmpty = false;
        $this->assertFalse($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Test keepalive (sending multiple messages in a single connection)
     */
    public function testSmtpKeepAlive()
    {
        $this->Mail->Body = 'This was done using the SMTP keep-alive.';
        $this->buildBody();
        $subject = $this->Mail->Subject;

        $this->Mail->SMTPKeepAlive = true;
        $this->Mail->Subject = $subject . ': SMTP keep-alive 1';
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);

        $this->Mail->Subject = $subject . ': SMTP keep-alive 2';
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
        $this->Mail->smtpClose();
    }

    /**
     * Test SMTP host connections
     */
    public function testSmtpConnect()
    {
        $this->assertTrue($this->Mail->smtpConnect(), 'SMTP single connect failed');
        $this->Mail->smtpClose();
        $this->Mail->Host = "ssl://localhost:12345;tls://localhost:587;10.10.10.10:54321;localhost:12345;10.10.10.10";
        $this->assertFalse($this->Mail->smtpConnect(), 'SMTP bad multi-connect succeeded');
        $this->Mail->smtpClose();
        $this->Mail->Host = "localhost:12345;10.10.10.10:54321;" . $_REQUEST['mail_host'];
        $this->assertTrue($this->Mail->smtpConnect(), 'SMTP multi-connect failed');
        $this->Mail->smtpClose();
        $this->Mail->Host = " localhost:12345 ; " . $_REQUEST['mail_host'] . ' ';
        $this->assertTrue($this->Mail->smtpConnect(), 'SMTP hosts with stray spaces failed');
        $this->Mail->smtpClose();
        $this->Mail->Host = $_REQUEST['mail_host'];
        //Need to pick a harmless option so as not cause problems of its own! socket:bind doesn't work with Travis-CI
        $this->assertTrue(
            $this->Mail->smtpConnect(array('ssl' => array('verify_depth' => 10))),
            'SMTP connect with options failed'
        );
    }

    /**
     * Tests this denial of service attack:
     *    http://www.cybsec.com/vuln/PHPMailer-DOS.pdf
     */
    public function testDenialOfServiceAttack()
    {
        $this->Mail->Body = 'This should no longer cause a denial of service.';
        $this->buildBody();

        $this->Mail->Subject = substr(str_repeat('0123456789', 100), 0, 998);
        $this->assertTrue($this->Mail->send(), $this->Mail->ErrorInfo);
    }

    /**
     * Tests this denial of service attack:
     *    https://sourceforge.net/p/phpmailer/bugs/383/
     * According to the ticket, this should get stuck in a loop, though I can't make it happen.
     */
    public function testDenialOfServiceAttack2()
    {
        //Encoding name longer than 68 chars
        $this->Mail->Encoding = '1234567890123456789012345678901234567890123456789012345678901234567890';
        //Call wrapText with a zero length value
        $this->Mail->wrapText(str_repeat('This should no longer cause a denial of service. ', 30), 0);
    }

    /**
     * Test error handling
     */
    public function testError()
    {
        $this->Mail->Subject .= ': This should be sent';
        $this->buildBody();
        $this->Mail->clearAllRecipients(); // no addresses should cause an error
        $this->assertTrue($this->Mail->isError() == false, 'Error found');
        $this->assertTrue($this->Mail->send() == false, 'send succeeded');
        $this->assertTrue($this->Mail->isError(), 'No error found');
        $this->assertEquals('You must provide at least one recipient email address.', $this->Mail->ErrorInfo);
        $this->Mail->addAddress($_REQUEST['mail_to']);
        $this->assertTrue($this->Mail->send(), 'send failed');
    }

    /**
     * Test addressing
     */
    public function testAddressing()
    {
        $this->assertFalse($this->Mail->addAddress(''), 'Empty address accepted');
        $this->assertFalse($this->Mail->addAddress('', 'Nobody'), 'Empty address with name accepted');
        $this->assertFalse($this->Mail->addAddress('a@example..com'), 'Invalid address accepted');
        $this->assertTrue($this->Mail->addAddress('a@example.com'), 'Addressing failed');
        $this->assertFalse($this->Mail->addAddress('a@example.com'), 'Duplicate addressing failed');
        $this->assertTrue($this->Mail->addCC('b@example.com'), 'CC addressing failed');
        $this->assertFalse($this->Mail->addCC('b@example.com'), 'CC duplicate addressing failed');
        $this->assertFalse($this->Mail->addCC('a@example.com'), 'CC duplicate addressing failed (2)');
        $this->assertTrue($this->Mail->addBCC('c@example.com'), 'BCC addressing failed');
        $this->assertFalse($this->Mail->addBCC('c@example.com'), 'BCC duplicate addressing failed');
        $this->assertFalse($this->Mail->addBCC('a@example.com'), 'BCC duplicate addressing failed (2)');
        $this->assertTrue($this->Mail->addReplyTo('a@example.com'), 'Replyto Addressing failed');
        $this->assertFalse($this->Mail->addReplyTo('a@example..com'), 'Invalid Replyto address accepted');
        $this->assertTrue($this->Mail->setFrom('a@example.com', 'some name'), 'setFrom failed');
        $this->assertFalse($this->Mail->setFrom('a@example.com.', 'some name'), 'setFrom accepted invalid address');
        $this->Mail->Sender = '';
        $this->Mail->setFrom('a@example.com', 'some name', true);
        $this->assertEquals($this->Mail->Sender, 'a@example.com', 'setFrom failed to set sender');
        $this->Mail->Sender = '';
        $this->Mail->setFrom('a@example.com', 'some name', false);
        $this->assertEquals($this->Mail->Sender, '', 'setFrom should not have set sender');
        $this->Mail->clearCCs();
        $this->Mail->clearBCCs();
        $this->Mail->clearReplyTos();
    }

    /**
     * Test address escaping
     */
    public function testAddressEscaping()
    {
        $this->Mail->Subject .= ': Address escaping';
        $this->Mail->clearAddresses();
        $this->Mail->addAddress('foo@example.com', 'Tim "The Book" O\'Reilly');
        $this->Mail->Body = 'Test correct escaping of quotes in addresses.';
        $this->buildBody();
        $this->Mail->preSend();
        $b = $this->Mail->getSentMIMEMessage();
        $this->assertTrue((strpos($b, 'To: "Tim \"The Book\" O\'Reilly" <foo@example.com>') !== false));
    }

    /**
     * Test BCC-only addressing
     */
    public function testBCCAddressing()
    {
        $this->Mail->Subject .= ': BCC-only addressing';
        $this->buildBody();
        $this->Mail->clearAllRecipients();
        $this->assertTrue($this->Mail->addBCC('a@example.com'), 'BCC addressing failed');
        $this->assertTrue($this->Mail->send(), 'send failed');
    }

    /**
     * Encoding and charset tests
     */
    public function testEncodings()
    {
        $this->Mail->CharSet = 'iso-8859-1';
        $this->assertEquals(
            '=A1Hola!_Se=F1or!',
            $this->Mail->encodeQ("\xa1Hola! Se\xf1or!", 'text'),
            'Q Encoding (text) failed'
        );
        $this->assertEquals(
            '=A1Hola!_Se=F1or!',
            $this->Mail->encodeQ("\xa1Hola! Se\xf1or!", 'comment'),
            'Q Encoding (comment) failed'
        );
        $this->assertEquals(
            '=A1Hola!_Se=F1or!',
            $this->Mail->encodeQ("\xa1Hola! Se\xf1or!", 'phrase'),
            'Q Encoding (phrase) failed'
        );
        $this->Mail->CharSet = 'UTF-8';
        $this->assertEquals(
            '=C2=A1Hola!_Se=C3=B1or!',
            $this->Mail->encodeQ("\xc2\xa1Hola! Se\xc3\xb1or!", 'text'),
            'Q Encoding (text) failed'
        );
        //Strings containing '=' are a special case
        $this->assertEquals(
            'Nov=C3=A1=3D',
            $this->Mail->encodeQ("Nov\xc3\xa1=", 'text'),
            'Q Encoding (text) failed 2'
        );
    }

    public function testBase64()
    {
        $this->Mail->Subject .= ': Base-64 encoding';
        $this->Mail->Encoding = 'base64';
        $this->buildBody();
        $this->assertTrue($this->Mail->send(), 'Base64 encoding failed');
    }
    /**
     * S/MIME Signing tests
     */
    public function testSigning()
    {
        $this->Mail->Subject .= ': S/MIME signing';
        $this->Mail->Body = 'This message is S/MIME signed.';
        $this->buildBody();

        $dn = array(
            'countryName' => 'UK',
            'stateOrProvinceName' => 'Here',
            'localityName' => 'There',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHPMailer',
            'commonName' => 'PHPMailer Test',
            'emailAddress' => 'phpmailer@example.com'
        );
        $password = 'password';
        $certfile = 'certfile.txt';
        $keyfile = 'keyfile.txt';

        //Make a new key pair
        $pk = openssl_pkey_new();
        //Create a certificate signing request
        $csr = openssl_csr_new($dn, $pk);
        //Create a self-signed cert
        $cert = openssl_csr_sign($csr, null, $pk, 1);
        //Save the cert
        openssl_x509_export($cert, $certout);
        file_put_contents($certfile, $certout);
        //Save the key
        openssl_pkey_export($pk, $pkeyout, $password);
        file_put_contents($keyfile, $pkeyout);

        $this->Mail->sign(
            $certfile,
            $keyfile,
            $password
        );
        $this->assertTrue($this->Mail->send(), 'S/MIME signing failed');
        unlink($certfile);
        unlink($keyfile);
    }

    /**
     * DKIM Signing tests
     */
    public function testDKIM()
    {
        $this->Mail->Subject .= ': DKIM signing';
        $this->Mail->Body = 'This message is DKIM signed.';
        $this->buildBody();
        //$this->Mail->SMTPDebug = 2;
        $privatekeyfile = 'dkim_private.key';
        //Make a new key pair
        //(2048 bits is the recommended minimum key length -
        //gmail won't accept less than 1024 bits)
        $pk = openssl_pkey_new(
            array(
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            )
        );
        openssl_pkey_export_to_file($pk, $privatekeyfile);
        $this->Mail->DKIM_domain = 'example.com';
        $this->Mail->DKIM_private = $privatekeyfile;
        $this->Mail->DKIM_selector = 'phpmailer';
        $this->Mail->DKIM_passphrase = ''; //key is not encrypted
        $this->assertTrue($this->Mail->send(), 'DKIM signed mail failed');
        unlink($privatekeyfile);
    }

    /**
     * Test line break reformatting
     */
    public function testLineBreaks()
    {
        $unixsrc = "hello\nWorld\nAgain\n";
        $macsrc = "hello\rWorld\rAgain\r";
        $windowssrc = "hello\r\nWorld\r\nAgain\r\n";
        $mixedsrc = "hello\nWorld\rAgain\r\n";
        $target = "hello\r\nWorld\r\nAgain\r\n";
        $this->assertEquals($target, PHPMailer::normalizeBreaks($unixsrc), 'UNIX break reformatting failed');
        $this->assertEquals($target, PHPMailer::normalizeBreaks($macsrc), 'Mac break reformatting failed');
        $this->assertEquals($target, PHPMailer::normalizeBreaks($windowssrc), 'Windows break reformatting failed');
        $this->assertEquals($target, PHPMailer::normalizeBreaks($mixedsrc), 'Mixed break reformatting failed');
    }

    /**
     * Use a fake POP3 server to test POP-before-SMTP auth
     * With a known-good login
     */
    public function testPopBeforeSmtpGood()
    {
        //Start a fake POP server
        $pid = shell_exec('nohup ./runfakepopserver.sh >/dev/null 2>/dev/null & printf "%u" $!');
        $this->pids[] = $pid;

        sleep(2);
        //Test a known-good login
        $this->assertTrue(
            POP3::popBeforeSmtp('localhost', 1100, 10, 'user', 'test', 0),
            'POP before SMTP failed'
        );
        //Kill the fake server
        shell_exec('kill -TERM '.escapeshellarg($pid));
        sleep(2);
    }

    /**
     * Use a fake POP3 server to test POP-before-SMTP auth
     * With a known-bad login
     */
    public function testPopBeforeSmtpBad()
    {
        //Start a fake POP server on a different port
        //so we don't inadvertently connect to the previous instance
        $pid = shell_exec('nohup ./runfakepopserver.sh 1101 >/dev/null 2>/dev/null & printf "%u" $!');
        $this->pids[] = $pid;

        sleep(2);
        //Test a known-bad login
        $this->assertFalse(
            POP3::popBeforeSmtp('localhost', 1101, 10, 'user', 'xxx', 0),
            'POP before SMTP should have failed'
        );
        shell_exec('kill -TERM '.escapeshellarg($pid));
        sleep(2);
    }

    /**
     * Test setting and retrieving message ID
     */
    public function testMessageID()
    {
        $this->Mail->Body = 'Test message ID.';
        $id = md5(12345);
        $this->Mail->MessageID = $id;
        $this->buildBody();
        $this->Mail->preSend();
        $lastid = $this->Mail->getLastMessageID();
        $this->assertEquals($lastid, $id, 'Custom Message ID mismatch');
    }

    /**
     * Miscellaneous calls to improve test coverage and some small tests
     */
    public function testMiscellaneous()
    {
        $this->assertEquals('application/pdf', PHPMailer::_mime_types('pdf'), 'MIME TYPE lookup failed');
        $this->Mail->addCustomHeader('SomeHeader: Some Value');
        $this->Mail->clearCustomHeaders();
        $this->Mail->clearAttachments();
        $this->Mail->isHTML(false);
        $this->Mail->isSMTP();
        $this->Mail->isMail();
        $this->Mail->isSendmail();
        $this->Mail->isQmail();
        $this->Mail->setLanguage('fr');
        $this->Mail->Sender = '';
        $this->Mail->createHeader();
        $this->assertFalse($this->Mail->set('x', 'y'), 'Invalid property set succeeded');
        $this->assertTrue($this->Mail->set('Timeout', 11), 'Valid property set failed');
        //Test pathinfo
        $a = '/mnt/files/飛兒樂 團光茫.mp3';
        $q = PHPMailer::mb_pathinfo($a);
        $this->assertEquals($q['dirname'], '/mnt/files', 'UNIX dirname not matched');
        $this->assertEquals($q['basename'], '飛兒樂 團光茫.mp3', 'UNIX basename not matched');
        $this->assertEquals($q['extension'], 'mp3', 'UNIX extension not matched');
        $this->assertEquals($q['filename'], '飛兒樂 團光茫', 'UNIX filename not matched');
        $this->assertEquals(
            PHPMailer::mb_pathinfo($a, PATHINFO_DIRNAME),
            '/mnt/files',
            'Dirname path element not matched'
        );
        $this->assertEquals(PHPMailer::mb_pathinfo($a, 'filename'), '飛兒樂 團光茫', 'Filename path element not matched');
        $a = 'c:\mnt\files\飛兒樂 團光茫.mp3';
        $q = PHPMailer::mb_pathinfo($a);
        $this->assertEquals($q['dirname'], 'c:\mnt\files', 'Windows dirname not matched');
        $this->assertEquals($q['basename'], '飛兒樂 團光茫.mp3', 'Windows basename not matched');
        $this->assertEquals($q['extension'], 'mp3', 'Windows extension not matched');
        $this->assertEquals($q['filename'], '飛兒樂 團光茫', 'Windows filename not matched');
    }
}

/**
 * This is a sample form for setting appropriate test values through a browser
 * These values can also be set using a file called testbootstrap.php (not in svn) in the same folder as this script
 * which is probably more useful if you run these tests a lot
 * <html>
 * <body>
 * <h3>phpmailer Unit Test</h3>
 * By entering a SMTP hostname it will automatically perform tests with SMTP.
 *
 * <form name="phpmailer_unit" action=__FILE__ method="get">
 * <input type="hidden" name="submitted" value="1"/>
 * From Address: <input type="text" size="50" name="mail_from" value="<?php echo get("mail_from"); ?>"/>
 * <br/>
 * To Address: <input type="text" size="50" name="mail_to" value="<?php echo get("mail_to"); ?>"/>
 * <br/>
 * Cc Address: <input type="text" size="50" name="mail_cc" value="<?php echo get("mail_cc"); ?>"/>
 * <br/>
 * SMTP Hostname: <input type="text" size="50" name="mail_host" value="<?php echo get("mail_host"); ?>"/>
 * <p/>
 * <input type="submit" value="Run Test"/>
 *
 * </form>
 * </body>
 * </html>
 */
