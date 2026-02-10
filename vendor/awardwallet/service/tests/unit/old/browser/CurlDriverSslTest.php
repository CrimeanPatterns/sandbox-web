<?php
namespace tests\unit\old\browser;


use PHPUnit\Framework\TestCase;

class CurlDriverSslTest extends TestCase {

    /**
     * @var \HttpBrowser
     */
    protected $browser;

    public function setUp(){
        parent::setUp();
        $this->markTestSkipped("run this when upgrading ssl libraries versions");
        $this->browser = new \HttpBrowser("none", new \CurlDriver());
        $this->browser->RetryCount = 0;
    }

    public function testSslJetblue(){
        $this->browser->userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36';
        $this->browser->GetURL("http://www.jetblue.com/default.aspx");
        $this->assertContains("trueblue.jetblue.com", $this->browser->Response['body']);
    }

    public function testSslFastpark(){
        $this->browser->GetURL("https://www.thefastpark.com/relaxforrewards/rfr-dashboard");
        $this->assertContains("Sign In To My Account", $this->browser->Response['body']);
    }

    public function testSslVerizon(){
        $this->browser->GetURL("https://login.verizonwireless.com/amserver/UI/Login");
        $this->assertContains("My Verizon", $this->browser->Response['body']);
    }

    public function testSslAna(){
        $this->browser->GetURL("https://stmt.cam.ana.co.jp/psz/amcj/jsp/renew/mile/reference_e.jsp");
        $this->assertContains("You must login to access the ANA", $this->browser->Response['body']);
    }

    public function testSslEmiles(){
        $this->browser->GetURL("https://emiles.com");
        $this->assertContains("loyl-srv.emiles.com/api/v1", $this->browser->Response['body']);
    }

    public function testSslErewards(){
        $this->browser->GetURL("https://www.e-rewards.com/reviewaccount.do");
        $this->assertContains("Member Login", $this->browser->Response['body']);
    }

    public function testSslWorldpoints(){
        $this->browser->GetURL("https://rewards.heathrow.com/web/lhr/heathrow-rewards");
        $this->assertContains("Heathrow Rewards", $this->browser->Response['body']);
    }

    public function testSslRedspot(){
        $this->browser->GetURL("https://tickets.redspottedhanky.com/rsh/en/account/login.aspx");
        $this->assertContains("edspottedhanky", $this->browser->Response['body']);
    }

    public function testSslChina(){
        $this->browser->GetURL("https://calec.china-airlines.com/dynasty-flyer/club.aspx?lang=en-us");
        $this->assertContains("Mileage Account", $this->browser->Response['body']);
    }

    public function testSslGamestop(){
//		$this->browser->SetProxy("localhost:8000");
        $this->markTestSkipped("proxy required");
        $this->browser->GetURL("https://login.gamestop.com/Account/Login?ReturnUrl=https%3a%2f%2fwww.gamestop.com%2fpoweruprewards%2fDashboard%2fIndex");
        $this->assertContains("Your Account", $this->browser->Response['body']);
    }

    public function testSslSportmaster(){
        $this->browser->GetURL("https://www.sportmaster.ru/user/profile/bonus.do");
        $this->assertContains("Войти", $this->browser->Response['body']);
    }

    public function testSslThanksAgain(){
        $this->browser->GetURL("https://member.thanksagain.com/sign-in");
        $this->assertContains("Thanks Again", $this->browser->Response['body']);
    }

    public function testSslBarclay(){
        $this->browser->GetURL("https://www.barclaycardus.com/app/ccsite/action/switchAccount");
        $this->assertContains("been inactive", $this->browser->Response['body']);
    }

    public function testSslBestbuy(){
        $this->browser->GetURL("https://www-ssl.bestbuy.com/identity/global/signin");
        $this->assertContains("Sign In to BestBuy.com", $this->browser->Response['body']);
    }

    public function testSslv3(){
        $this->browser->GetURL("https://www.lennys.com/rewards/index.cfm");
        $this->assertContains("VIP Rewards", $this->browser->Response['body']);
    }

}