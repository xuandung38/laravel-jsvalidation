<?php namespace Proengsoft\JsValidation\Traits;



use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait RemoteValidation
{

    /**
     * Get the data under validation.
     * @return array
     */
    public abstract function getData();

    /**
     * Set the data under validation.
     * @param  array  $data
     * @return void
     */
    public abstract function setData(array $data);


    /**
     * Get the message container for the validator.
     * @return \Illuminate\Support\MessageBag
     */
    public abstract function messages();


    /**
     * Get the array of custom validator extensions.
     * @return array
     */
    public abstract function getExtensions();


    /**
     * Get the validation rules.
     * @return array
     */
    public abstract function getRules();


    /**
     * Set the validation rules.
     * @param  array  $rules
     * @return $this
     */
    public abstract function setRules(array $rules);

    /**
     * Extract the rule name and parameters from a rule.
     *
     * @param  array|string  $rules
     * @return array
     */
    protected abstract function parseRule($rules);


    /**
     * Validate remote Javascript Validations
     *
     * @param $attribute
     * @param $callable
     */
    protected function validateJsRemoteRequest($attribute, $callable)
    {
        if (!$this->setRemoteValidationData($attribute)) {
            throw new BadRequestHttpException("Bad request");
        }

        $message=call_user_func($callable);
        if (!$message) {
            $message=$this->messages()->get($attribute);
        }

        throw new HttpResponseException(
            new JsonResponse($message, 200))
        ;
    }


    /**
     *  Check if Request must be validated by JsValidation
     *
     * @return bool
     */
    protected function isRemoteValidationRequest()
    {
        $data=$this->getData();
        return !empty($data['_jsvalidation']);
    }


    /**
     * Sets data for validate remote rules
     *
     * @param $attribute
     * @return bool
     */
    protected function setRemoteValidationData($attribute)
    {
        if ( ! array_key_exists($attribute, $this->getRules()))
        {
            $this->setRules(array());
            return false;
        }

        $rules=$this->getRules()[$attribute];

        foreach ($rules as $i=>$rule) {
            $parsedRule=$this->parseRule($rule);
            if (!$this->isRemoteRule($parsedRule[0])) {
                unset($rules[$i]);
            }
        }

        $this->setRules([$attribute=>$rules]);

        return !empty($this->getRules()[$attribute]);
    }



    /**
     * Check if rule must be validated remotely
     *
     * @param string $rule
     * @return bool
     */
    protected function isRemoteRule($rule)
    {
        if (!in_array($rule,['ActiveUrl','Exists', 'Unique']))
        {
            return in_array(snake_case($rule), array_keys($this->getExtensions()));
        }

        return true;
    }


}
