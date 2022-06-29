<?php

namespace Braintree;

/**
 * Braintree Customer module
 * Creates and manages Customers
 *
 * <b>== More information ==</b>
 *
 * // phpcs:ignore Generic.Files.LineLength
 * For more detailed information on Customers, see {@link https://developers.braintreepayments.com/reference/response/customer/php https://developers.braintreepayments.com/reference/response/customer/php}
 *
 * @package    Braintree
 * @category   Resources
 *
 * @property-read \Braintree\Address[] $addresses
 * @property-read \Braintree\GooglePayCard[] $googlePayCards
 * @property-read \Braintree\ApplePayCard[] $applePayCards
 * @property-read string $company
 * @property-read \DateTime $createdAt
 * @property-read \Braintree\CreditCard[] $creditCards
 * @property-read array  $customFields custom fields passed with the request
 * @property-read string $email
 * @property-read string $fax
 * @property-read string $firstName
 * @property-read string $graphQLId
 * @property-read string $id
 * @property-read string $lastName
 * @property-read \Braintree\PaymentMethod[] $paymentMethods
 * @property-read \Braintree\PayPalAccount[] $paypalAccounts
 * @property-read string $phone
 * @property-read \Braintree\SamsungPayCard[] $samsungPayCards
 * @property-read \DateTime $updatedAt
 * @property-read \Braintree\UsBankAccount[] $usBankAccounts
 * @property-read \Braintree\VenmoAccount[] $venmoAccounts
 * @property-read \Braintree\VisaCheckoutCard[] $visaCheckoutCards
 * @property-read string $website
 */
class Customer extends Base
{
    /**
     *
     * @return Customer[]
     */
    public static function all()
    {
        return Configuration::gateway()->customer()->all();
    }

    /**
     *
     * @param array $query
     * @param int[] $ids
     * @return Customer|Customer[]
     */
    public static function fetch($query, $ids)
    {
        return Configuration::gateway()->customer()->fetch($query, $ids);
    }

    /**
     *
     * @param array $attribs
     * @return Result\Successful|Result\Error
     */
    public static function create($attribs = [])
    {
        return Configuration::gateway()->customer()->create($attribs);
    }

    /**
     *
     * @param array $attribs
     * @return Customer
     */
    public static function createNoValidate($attribs = [])
    {
        return Configuration::gateway()->customer()->createNoValidate($attribs);
    }

    /**
     *
     * @throws Exception\NotFound
     * @param string $id customer id
     * @return Customer
     */
    public static function find($id, $associationFilterId = null)
    {
        return Configuration::gateway()->customer()->find($id, $associationFilterId);
    }

    /**
     *
     * @param int $customerId
     * @param array $transactionAttribs
     * @return Result\Successful|Result\Error
     */
    public static function credit($customerId, $transactionAttribs)
    {
        return Configuration::gateway()->customer()->credit($customerId, $transactionAttribs);
    }

    /**
     *
     * @throws Exception\ValidationError
     * @param type $customerId
     * @param type $transactionAttribs
     * @return Transaction
     */
    public static function creditNoValidate($customerId, $transactionAttribs)
    {
        return Configuration::gateway()->customer()->creditNoValidate($customerId, $transactionAttribs);
    }

    /**
     *
     * @throws Exception on invalid id or non-200 http response code
     * @param int $customerId
     * @return Result\Successful
     */
    public static function delete($customerId)
    {
        return Configuration::gateway()->customer()->delete($customerId);
    }

    /**
     *
     * @param int $customerId
     * @param array $transactionAttribs
     * @return Transaction
     */
    public static function sale($customerId, $transactionAttribs)
    {
        return Configuration::gateway()->customer()->sale($customerId, $transactionAttribs);
    }

    /**
     *
     * @param int $customerId
     * @param array $transactionAttribs
     * @return Transaction
     */
    public static function saleNoValidate($customerId, $transactionAttribs)
    {
        return Configuration::gateway()->customer()->saleNoValidate($customerId, $transactionAttribs);
    }

    /**
     *
     * @throws InvalidArgumentException
     * @param array $query
     * @return ResourceCollection
     */
    public static function search($query)
    {
        return Configuration::gateway()->customer()->search($query);
    }

    /**
     *
     * @throws Exception\Unexpected
     * @param int $customerId
     * @param array $attributes
     * @return Result\Successful|Result\Error
     */
    public static function update($customerId, $attributes)
    {
        return Configuration::gateway()->customer()->update($customerId, $attributes);
    }

    /**
     *
     * @throws Exception\Unexpected
     * @param int $customerId
     * @param array $attributes
     * @return CustomerGateway
     */
    public static function updateNoValidate($customerId, $attributes)
    {
        return Configuration::gateway()->customer()->updateNoValidate($customerId, $attributes);
    }

    /* instance methods */

    /**
     * sets instance properties from an array of values
     *
     * @ignore
     * @access protected
     * @param array $customerAttribs array of customer data
     */
    protected function _initialize($customerAttribs)
    {
        $this->_attributes = $customerAttribs;

        $addressArray = [];
        if (isset($customerAttribs['addresses'])) {
            foreach ($customerAttribs['addresses'] as $address) {
                $addressArray[] = Address::factory($address);
            }
        }
        $this->_set('addresses', $addressArray);

        $creditCardArray = [];
        if (isset($customerAttribs['creditCards'])) {
            foreach ($customerAttribs['creditCards'] as $creditCard) {
                $creditCardArray[] = CreditCard::factory($creditCard);
            }
        }
        $this->_set('creditCards', $creditCardArray);

        $paypalAccountArray = [];
        if (isset($customerAttribs['paypalAccounts'])) {
            foreach ($customerAttribs['paypalAccounts'] as $paypalAccount) {
                $paypalAccountArray[] = PayPalAccount::factory($paypalAccount);
            }
        }
        $this->_set('paypalAccounts', $paypalAccountArray);

        $applePayCardArray = [];
        if (isset($customerAttribs['applePayCards'])) {
            foreach ($customerAttribs['applePayCards'] as $applePayCard) {
                $applePayCardArray[] = ApplePayCard::factory($applePayCard);
            }
        }
        $this->_set('applePayCards', $applePayCardArray);

        $googlePayCardArray = [];
        if (isset($customerAttribs['androidPayCards'])) {
            foreach ($customerAttribs['androidPayCards'] as $googlePayCard) {
                $googlePayCardArray[] = GooglePayCard::factory($googlePayCard);
            }
        }
        $this->_set('googlePayCards', $googlePayCardArray);

        $venmoAccountArray = array();
        if (isset($customerAttribs['venmoAccounts'])) {
            foreach ($customerAttribs['venmoAccounts'] as $venmoAccount) {
                $venmoAccountArray[] = VenmoAccount::factory($venmoAccount);
            }
        }
        $this->_set('venmoAccounts', $venmoAccountArray);

        $visaCheckoutCardArray = [];
        if (isset($customerAttribs['visaCheckoutCards'])) {
            foreach ($customerAttribs['visaCheckoutCards'] as $visaCheckoutCard) {
                $visaCheckoutCardArray[] = VisaCheckoutCard::factory($visaCheckoutCard);
            }
        }
        $this->_set('visaCheckoutCards', $visaCheckoutCardArray);

        $samsungPayCardArray = [];
        if (isset($customerAttribs['samsungPayCards'])) {
            foreach ($customerAttribs['samsungPayCards'] as $samsungPayCard) {
                $samsungPayCardArray[] = SamsungPayCard::factory($samsungPayCard);
            }
        }
        $this->_set('samsungPayCards', $samsungPayCardArray);

        $usBankAccountArray = array();
        if (isset($customerAttribs['usBankAccounts'])) {
            foreach ($customerAttribs['usBankAccounts'] as $usBankAccount) {
                $usBankAccountArray[] = UsBankAccount::factory($usBankAccount);
            }
        }
        $this->_set('usBankAccounts', $usBankAccountArray);

        $this->_set('paymentMethods', array_merge(
            $this->creditCards,
            $this->paypalAccounts,
            $this->applePayCards,
            $this->googlePayCards,
            $this->venmoAccounts,
            $this->visaCheckoutCards,
            $this->samsungPayCards,
            $this->usBankAccounts
        ));

        $customFields = [];
        if (isset($customerAttribs['customFields'])) {
            $customFields = $customerAttribs['customFields'];
        }
        $this->_set('customFields', $customFields);
    }

    /**
     * returns a string representation of the customer
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '[' .
                Util::attributesToString($this->_attributes) . ']';
    }

    /**
     * returns false if comparing object is not a Customer,
     * or is a Customer with a different id
     *
     * @param object $otherCust customer to compare against
     * @return boolean
     */
    public function isEqual($otherCust)
    {
        return !($otherCust instanceof Customer) ? false : $this->id === $otherCust->id;
    }

    /**
     * returns the customer's default payment method
     *
     * @return CreditCard|PayPalAccount
     */
    public function defaultPaymentMethod()
    {
        $defaultPaymentMethods = array_filter($this->paymentMethods, 'Braintree\Customer::_defaultPaymentMethodFilter');
        return current($defaultPaymentMethods);
    }

    public static function _defaultPaymentMethodFilter($paymentMethod)
    {
        return $paymentMethod->isDefault();
    }

    /* private class properties  */

    /**
     * @access protected
     * @var array registry of customer data
     */
    protected $_attributes = [
        'addresses'      => '',
        'company'        => '',
        'creditCards'    => '',
        'email'          => '',
        'fax'            => '',
        'firstName'      => '',
        'id'             => '',
        'lastName'       => '',
        'phone'          => '',
        'taxIdentifiers' => '',
        'createdAt'      => '',
        'updatedAt'      => '',
        'website'        => '',
        ];

    /**
     *  factory method: returns an instance of Customer
     *  to the requesting method, with populated properties
     *
     * @ignore
     * @param array $attributes
     * @return Customer
     */
    public static function factory($attributes)
    {
        $instance = new Customer();
        $instance->_initialize($attributes);
        return $instance;
    }
}
