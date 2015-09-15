<?php

namespace PayPal\Validation;

use PayPal\Common\PayPalModel;
use PayPal\Core\PayPalConfigManager;
use PayPal\Core\PayPalLoggingManager;

/**
 * Class ModelAccessorValidator
 *
 * @package PayPal\Validation
 */
class ModelAccessorValidator
{
    /**
     * Helper method for validating if the class contains accessor methods (getter and setter) for a given attribute
     *
     * @param PayPalModel $class An object of PayPalModel
     * @param string $attributeName Attribute name
     * @return bool
     */
    public static function validate(PayPalModel $class, $attributeName)
    {
        $mode = PayPalConfigManager::getInstance()->get('validation.level');
        if (!empty($mode) && $mode != 'disabled') {
            //Check if $attributeName is string
            if (gettype($attributeName) !== 'string') {
                return false;
            }
            //If the mode is disabled, bypass the validation
            foreach (array('set' . $attributeName, 'get' . $attributeName) as $methodName) {
                if (get_class($class) == get_class(new PayPalModel())) {
                    // Silently return false on cases where you are using PayPalModel instance directly
                    return false;
                }
                //Check if both getter and setter exists for given attribute
                elseif (!method_exists($class, $methodName)) {
                    //Delegate the error based on the choice
                    $className = is_object($class) ? get_class($class) : (string)$class;
                    $errorMessage = "Missing Accessor: $className:$methodName. You might be using older version of SDK. If not, create an issue at https://github.com/paypal/PayPal-PHP-SDK/issues";
                    PayPalLoggingManager::getInstance(__CLASS__)->debug($errorMessage);
                    if ($mode == 'strict') {
                        trigger_error($errorMessage, E_USER_NOTICE);
                    }
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}
