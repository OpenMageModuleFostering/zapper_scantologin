<?php
    class Zapper_ScanToLogin_Helper_Data extends Mage_Core_Helper_Abstract
    {
        function bootstrap()
        {       
            $selfRegistrationAllowed = true;
            $demoMode = false;
            
            $baseUrl = Mage::getBaseUrl() . 'scantologin';
            
            $sandbox = intval(Mage::getStoreConfig('zapper/scan_to_login/sandbox', Mage::app()->getStore()));
            $api_url = 'https://zapapi.zapzap.mobi/zappertech';
            if ($sandbox == '1')
            {
                $merchantId = '2127';
                $siteId = '2490';
            }
            else
            {
                $merchantId = Mage::getStoreConfig('zapper/config/merchant_id', Mage::app()->getStore());
                $siteId = Mage::getStoreConfig('zapper/scan_to_login/site_id', Mage::app()->getStore());
            }       
            $qrSize = Mage::getStoreConfig('zapper/config/qrsize', Mage::app()->getStore());
            $timeout = Mage::getStoreConfig('zapper/config/timeout', Mage::app()->getStore());
            $enable_scan = intval(Mage::getStoreConfig('zapper/scan_to_login/active', Mage::app()->getStore()));
            $selector = Mage::getStoreConfig('zapper/scan_to_login/selector', Mage::app()->getStore());
            $additionalParameters = Mage::getStoreConfig('zapper/scan_to_login/additionalparameters', Mage::app()->getStore());
    
            if($enable_scan == 1) { ?>
            <script type='text/javascript'>jQuery.noConflict();</script>
            <script>
            (function($){
                $(document).ready(function () {
                    var baseUrl = '<?php echo $baseUrl; ?>/';
                    var allowRegistration = <?php echo $selfRegistrationAllowed; ?>;
                  
                    var placeHolder = $('<div id="scantologin-container"><div id="logo-container" class="zapperLogo"><div class="scantologin-qrcode-placeholder"></div></div><div class="scantologin-prompt"></div><div id="scantologin-end-container"><span id="scantologin-available-for"></span><a href="http://www.zapper.com/" target="_blank" id="scantologin-zapper-link">www.zapper.com</a></div></div>');                    
               
                    <?php print ($selector == 'body') ? "$('body').html(placeHolder);" : "$('#" . $selector . "').prepend(placeHolder);"; ?>
                    var qrCode = new ZapperTech.QrCode
                    ({                    
                        baseUrl: "<?php echo $api_url?>",
                        merchantId: <?php echo intval($merchantId) ?>,
                        siteId: <?php echo intval($siteId) ?>,  
                        qrSize: <?php echo $qrSize ? intval($qrSize) : 7 ?>,
                        timeout: <?php echo $timeout ? intval($timeout) : 5000 ?>,
                        selector: placeHolder,
                        additionalParameters: '<?php echo $additionalParameters ? $additionalParameters : '[]' ?>',
                    });
                    qrCode.registrationRequest(function(data){
                        if(!allowRegistration) {
                            var error = 'This is does not allow self registration.';
                            alert(error);
                            qrCode.registrationRespond({
                                success: false,
                                errors: [error],
                                username: '',
                                password: ''
                            });
                        } else {
                            var email = data.getAnswer(qrCode.QUESTIONTYPE.email);
                            var firstName = data.getAnswer(qrCode.QUESTIONTYPE.firstName);
                            var lastName = data.getAnswer(qrCode.QUESTIONTYPE.lastName);
                            var password = data.Password;
                            var userName = data.getAnswer(qrCode.QUESTIONTYPE.userName);
                            var address1 = data.getAnswer(qrCode.QUESTIONTYPE.address1);
                            var address2 = data.getAnswer(qrCode.QUESTIONTYPE.address2);
                            var city = data.getAnswer(qrCode.QUESTIONTYPE.city);
                            var postcode = data.getAnswer(qrCode.QUESTIONTYPE.postcode);
                            var telephone = data.getAnswer(qrCode.QUESTIONTYPE.telephone);
                            var country = data.getAnswer(qrCode.QUESTIONTYPE.country);
                            $.ajax({
                            url: baseUrl + '?zappertech=register',
                            type: 'POST',
                            data: 
                            {
                                firstName: firstName,
                                lastName: lastName,
                                email: email,
                                password: password,
                                userName:userName,
                                address1: address1,
                                address2: address2,
                                city: city,
                                postcode: postcode,
                                telephone: telephone,
                                country: country
                            },
                            dataType: 'json'
                        }).done(function(result){
                            var response = {
                                success: result.success,
                                errors: result.errors,
                                username: result.username,
                                password: result.password
                            };
                            qrCode.registrationRespond(response);
                            if(result.success === false) { 
                                var alertMessage = "Registration failed: \n";
                                $(response.errors).each(function(i, error) {
                                    alertMessage += error + "\n";
                                });
                                alert(alertMessage);
                            } else {
                                login({
                                    Username: result.username,
                                    Password: result.password
                                });
                            }
                        });
                        }
                    });
                    var login = function(data){
                        $.ajax({
                            url: baseUrl + '?zappertech=authenticate',
                            type: 'POST',
                            data: {
                                username: data.Username,
                                password: data.Password
                            },
                            dataType: 'json',
                            complete: function(result){
                                result = eval('(' + result.responseText + ')');
                                var response = {
                                    success: result.success,
                                    errors: result.errors,
                                    username: result.username,
                                    password: result.password
                                };
                                qrCode.loginRespond(response);
                                $('#user_login').val(response.username);
                                $('#user_pass').remove();
                                $('label[for="user_pass"]').append($('<input type="password" size="20" value="" class="input" id="user_pass_dummy" name="pwd_dummy">').val(response.username));
                                $('label[for="user_pass"]').append($('<input type="hidden" size="20" value="" class="input" id="user_pass" name="pwd">').val(response.password));
                                window.location.reload();
                            }
                        });
                    };
                    
                    qrCode.loginRequest(login);
                    qrCode.start()
                });
            })(jQuery);
            </script>
            <?php }
        }

        function hijack() {
            if(isset($_GET['zappertech'])) 
            { 
                switch ($_GET['zappertech']) 
                {
                    case 'bootstrap':
                        $this->bootstrap();
                        break;
                    case 'register':
                        $this->register_user();
                        break;
                    case 'authenticate':
                        $this->authenticate_user();
                        break;
                }
                exit;
            } 
        }    
        
        function register_user() {
            $response = array();        
            mage::log($_POST);
            $userName = $_POST['userName'];
            $firstName = $_POST['firstName'];
            $lastName = $_POST['lastName'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            //Validation
            $response['errors'] = array();
            $response['success'] = FALSE;
            $response['username'] = '';
            $response['password'] = '';
    
            $websiteId = Mage::app()->getWebsite()->getId();
            
            //$customer = new Mage_Customer_Model_Customer();
            $customer = Mage::getModel("customer/customer");
    
            $customer->setWebsiteId($websiteId);        
            $customer->loadByEmail($email);
    
            if(empty($firstName))
                $response['errors'][] = 'Please enter a first name.';
            if(empty($lastName))
                $response['errors'][] = 'Please enter a last name.';
            if(empty($email))
                $response['errors'][] = 'Please enter an email address.';
            if($customer->getId())
             {
                //$this->authenticate_user();
                $response['errors'][] = 'Email address already registered.';
             }   
                
            if(empty($password))
                $response['errors'][] = 'Please enter a password.';
            //Registration
            if(empty($response['errors'])) 
            {                
                $store = Mage::app()->getStore();
    
                $customer->website_id = $websiteId;
                $customer->setStore($store);
    
                $customer->username = $userName;
                $customer->firstname = $firstName;
                $customer->lastname = $lastName;
                $customer->email = $email;
                $customer->password_hash = md5($password);            
                $customer->save();
                
                $customer->setConfirmation(null);
                $customer->setStatus(1);
                $customer->save();
    
                $_custom_address = array 
                (
                'firstname' => $firstName,
                'lastname' => $lastName,
                'street' => array (
                    '0' => $_POST['address1'],
                    '1' => $_POST['address2'],
                ),
                'city' => $_POST['city'],
                'region_id' => '',
                'region' => '',
                'postcode' => $_POST['postcode'],
                'country_id' => 'ZA',//$_POST['country'], 
                'telephone' => $_POST['telephone'],
                );
    
                $customAddress = Mage::getModel('customer/address');
    
                $customAddress->setData($_custom_address)
                        ->setCustomerId($customer->getId())
                        ->setIsDefaultBilling('1')
                        ->setIsDefaultShipping('1')
                        ->setSaveInAddressBook('1');
    
                try { $customAddress->save(); } catch (Exception $e) {}
    
                $response['success'] = TRUE;
                $response['username'] = $email;
                $response['password'] = $password;
            }
    
            $this->json_encoded($response);
        }
    
        function authenticate_user()
        {        
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            $response = array();
            $response['errors'] = array();
            $response['success'] = FALSE;
            $response['username'] = $username;
            $response['password'] = $password;
    
            $session = Mage::getSingleton('customer/session');
            try {
                $session->login($username, $password);   
                $user = $session->getCustomer();
                
                $response['success'] = TRUE;
                $response['username'] = $username;
                $response['password'] = $password;
            
            } catch (Exception $e) {
                $response['errors'] = $e->getMessage();
            }        
    
            $this->json_encoded($response);
        }
        
        
        function json_encoded($data) 
        {
            @header('Cache-Control: no-cache, must-revalidate');
            @header('Expires: Mon, 26 July 1997 05:00:00 GMT');
            @header('Content-type: application/json');
            echo json_encode($data);
        }
        
        function render($type = 0)
        {
            $this->bootstrap();        
            $this->hijack();     
        }
    }     