<?php
/**
 *
 * This file is part of the Aura project for PHP.
 *
 * @package Aura.Input
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Input;

use Aura\Filter_Interface\FilterInterface;
use Aura\Filter_Interface\FailureCollectionInterface;
use Aura\Input\Filter\FailureCollection;

/**
 *
 * A filter
 *
 * @package Aura.Input
 *
 */
class Filter implements FilterInterface
{
    /**
     *
     * The array of rules to be applied to fields.
     *
     * @var array
     *
     */
    protected $rules = [];

    /**
     *
     * The array of failures to be used when rules fail.
     *
     * @var FailureCollection
     *
     */
    protected $failures;

    /**
     *
     * A prototype FailureCollection.
     *
     * @var FailureCollection
     *
     */
    protected $proto_failures;

    /**
     * Initialize filters
     */
    public function __construct(FailureCollectionInterface $failures = null)
    {
        if ($failures === null) {
            $failures = new FailureCollection();
        }
        $this->proto_failures = $failures;
        $this->init();
    }

    /**
     *
     * Does nothing
     *
     */
    protected function init()
    {
        # code...
    }

    /**
     *
     * Resets all previous filter rules for the field and add the rule.
     *
     * @param string $field The field name.
     *
     * @param string $message The message when the rule fails.
     *
     * @param \Closure $closure A closure that implements the rule. It must
     * have the signature `function ($value, &$fields)`; it must return
     * boolean true on success, or boolean false on failure.
     *
     */
    public function setRule($field, $message, \Closure $closure)
    {
        unset($this->rules[$field]);
        $this->addRule($field, $message, $closure);
    }

    /**
     *
     * Add multiple rules to a field.
     *
     * @param string $field The field name.
     *
     * @param string $message The message when the rule fails.
     *
     * @param \Closure $closure A closure that implements the rule. It must
     * have the signature `function ($value, &$fields)`; it must return
     * boolean true on success, or boolean false on failure.
     *
     */
    public function addRule($field, $message, \Closure $closure)
    {
        $this->rules[$field][] = [$message, $closure];
    }

    /**
     *
     * Filter (sanitize and validate) the data.
     *
     * @param mixed $values The values to be filtered.
     *
     * @return bool True if all rules passed; false if one or more failed.
     *
     */
    public function apply(&$values)
    {
        $this->failures = clone $this->proto_failures;

        // go through each field rules
        foreach ($this->rules as $field => $rules) {
            foreach ($rules as $rule) {
                // get the message and closure
                list($message, $closure) = $rule;

                // apply the closure to the data and get back the result
                $passed = $closure($values->$field, $values);

                if (! $passed) {
                    $this->failures->addMessagesForField($field, $message);
                }
            }
        }

        // Is the failures empty or not
        return $this->failures->isEmpty() ? true : false;
    }

    /**
     *
     * Gets the messages for all fields
     *
     * @return FailureCollection
     *
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     *
     * Gets the messages for all fields, or for a single field.
     *
     * @param string $field If empty, return all messages for all fields;
     * otherwise, return only messages for the named field.
     *
     * @return array
     *
     */
    public function getMessages($field = null)
    {
        if ($field === null) {
            return $this->failures->getMessages();
        }

        return $this->failures->getMessagesForField($field);
    }

    /**
     *
     * Manually add messages to a particular field.
     *
     * @param string $field Add to this field.
     *
     * @param string|array $messages Add these messages to the field.
     *
     * @return void
     *
     */
    public function addMessages($field, $messages)
    {
        $this->failures->addMessagesForField($field, $messages);
    }
}
