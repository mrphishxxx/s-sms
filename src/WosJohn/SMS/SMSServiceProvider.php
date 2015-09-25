<?php namespace WosJohn\SMS;

/**
 * Simple-SMS
 * Simple-SMS is a package made for Laravel to send/receive (polling/pushing) text messages.
 *
 * @link http://www.simplesoftware.io
 * @author SimpleSoftware support@simplesoftware.io
 *
 */

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use WosJohn\SMS\Drivers\CallFireSMS;
use WosJohn\SMS\Drivers\EmailSMS;
use WosJohn\SMS\Drivers\EZTextingSMS;
use WosJohn\SMS\Drivers\MozeoSMS;
use WosJohn\SMS\Drivers\TwilioSMS;
use WosJohn\SMS\Drivers\CCPSMS;

class SMSServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/sms.php' => config_path('sms.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('sms', function ($app) {

            $sender = $this->registerSender();

            $sms = new SMS($sender);

            $sms->setContainer($app);
            $sms->setLogger($app['log']);
            $sms->setQueue($app['queue']);

            //Set the from and pretending settings
            if ($from = config('sms.from', false)) $sms->alwaysFrom($from);
            $sms->setPretending(config('sms.pretend', false));

            return $sms;
        });
    }

    /**
     * Register the correct driver based on the config file.
     *
     * @return CallFireSMS|EmailSMS|EZTextingSMS|MozeoSMS|TwilioSMS|CCPSMS
     * @throws \InvalidArgumentException
     */
    public function registerSender()
    {
        $driver = config('sms.driver');

        switch ($driver) {
            case 'email':
                return new EmailSMS($this->app['mailer']);

            case 'twilio':
                return $this->buildTwilio();

            case 'twilio':
                return $this->buildCCP();

            case 'eztexting':
                return $this->buildEZTexting();

            case 'callfire':
                return $this->buildCallFire();

            case 'mozeo':
                return $this->buildMozeo();

            default:
                throw new \InvalidArgumentException('Invalid SMS driver.');
        }
    }

    protected function buildCCP()
    {
        return new CCPSMS(
            new \Services_CCP(
                config('sms.ccp.account_sid'),
                config('sms.ccp.auth_token'),
                config('sms.ccp.app_id')
            ),
            config('sms.ccp.auth_token'),
            $this->app['request']->url()
        );
    }

    protected function buildTwilio()
    {
        return new TwilioSMS(
            new \Services_Twilio(
                config('sms.twilio.account_sid'),
                config('sms.twilio.auth_token')
            ),
            config('sms.twilio.auth_token'),
            $this->app['request']->url(),
            config('sms.twilio.verify')
        );
    }

    protected function buildEZTexting()
    {
        $provider = new EZTextingSMS(new Client);

        $data = [
            'User' => config('sms.eztexting.username'),
            'Password' => config('sms.eztexting.password')
        ];

        $provider->buildBody($data);

        return $provider;
    }

    protected function buildCallFire()
    {
        $provider = new CallFireSMS(new Client);

        $provider->setUser(config('sms.callfire.app_login'));
        $provider->setPassword(config('sms.callfire.app_password'));

        return $provider;
    }

    protected function buildMozeo()
    {
        $provider = new MozeoSMS(new Client);

        $auth = [
            'companykey' => config('sms.mozeo.companyKey'),
            'username' => config('sms.mozeo.username'),
            'password' => config('sms.mozeo.password'),
        ];

        $provider->buildBody($auth);

        return $provider;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('sms', 'emailsms', 'twiliosms','ccpsms', 'mozeosms', 'eztextingsms', 'callfiresms');
    }

}
