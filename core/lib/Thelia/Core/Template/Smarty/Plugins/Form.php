<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	    email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/
namespace Thelia\Core\Template\Smarty\Plugins;

use Symfony\Component\Form\FormView;
use Thelia\Core\Form\Type\TheliaType;

use Thelia\Core\Template\Element\Exception\ElementNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Core\Template\Smarty\SmartyPluginDescriptor;
use Thelia\Core\Template\Smarty\AbstractSmartyPlugin;
use Thelia\Core\Template\ParserContext;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

/**
 *
 * Plugin for smarty defining blocks and functions for using Form display.
 *
 * blocks :
 *  - {form name="myForm"} ... {/form} => find form named myForm,
 * create an instance and assign this instanciation into smarty variable. Form must be declare into
 * config using <forms> tag
 *
 *  - {form_field form=$form.fieldName} {/form_field} This block find info into the Form field containing by
 * the form paramter. This field must be an instance of FormView. fieldName is the name of your field. This block
 * can output these info :
 *      * $name => name of yout input
 *      * $value => value for your input
 *      * $label => label for your input
 *      * $error => boolean for know if there is error for this field
 *      * $attr => all your attribute for your input (define when you construct programmatically you form)
 *
 *  - {form_error form=$form.fieldName} ... {/form_error} Display this block if there are errors on this field.
 * fieldName is the name of your field
 *
 * Class Form
 * @package Thelia\Core\Template\Smarty\Plugins
 */
class Form extends AbstractSmartyPlugin
{
    private static $taggedFieldsStack = null;
    private static $taggedFieldsStackPosition = null;

    protected $request;
    protected $parserContext;

    protected $formDefinition = array();

    public function __construct(Request $request, ParserContext $parserContext)
    {
        $this->request = $request;
        $this->parserContext = $parserContext;
    }

    public function setFormDefinition($formDefinition)
    {
        foreach ($formDefinition as $name => $className) {
            if (array_key_exists($name, $this->formDefinition)) {
                throw new \InvalidArgumentException(
                        sprintf("%s form name already exists for %s class", $name, $className));
            }

            $this->formDefinition[$name] = $className;
        }
    }

    public function generateForm($params, $content, \Smarty_Internal_Template $template, &$repeat)
    {

        if ($repeat) {

            $name = $this->getParam($params, 'name');

            if (null == $name) {
                throw new \InvalidArgumentException("Missing 'name' parameter in form arguments");
            }

            if (!isset($this->formDefinition[$name])) {
                throw new ElementNotFoundException(sprintf("%s form does not exists", $name));
            }

            $formClass = $this->formDefinition[$name];

            // Check if parser context contains our form
            $form = $this->parserContext->getForm($formClass);

            if (null != $form) {
                // Re-use the form
                $instance = $form;
            } else {
                // Create a new one
                $class = new \ReflectionClass($formClass);

                $instance = $class->newInstance($this->request, "form");
            }

            $instance->createView();

            $template->assign("form", $instance);

            $template->assign("form_error", $instance->hasError() ? true : false);
            $template->assign("form_error_message", $instance->getErrorMessage());
        } else {
            return $content;
        }
    }

    protected function assignFieldValues($template, $fieldName, $fieldValue, $fieldVars, $total_value_count = 1)
    {
        $template->assign("name", $fieldName);

        $template->assign("value", $fieldValue);

        $template->assign("checked", isset($fieldVars['checked']) ? $fieldVars['checked'] : false);

        //data
        $template->assign("data", $fieldVars['data']);

        $template->assign("label", $fieldVars["label"]);
        $template->assign("label_attr", $fieldVars["label_attr"]);

        $template->assign('required', isset($fieldVars['required']) ? $fieldVars['required'] : false);

        $template->assign('total_value_count', $total_value_count);

        $errors = $fieldVars["errors"];

        $template->assign("error", empty($errors) ? false : true);

        if (!empty($errors)) {
            $this->assignFieldErrorVars($template, $errors);
        }

        $attr = array();

        foreach ($fieldVars["attr"] as $key => $value) {
            $attr[] = sprintf('%s="%s"', $key, $value);
        }

        $template->assign("attr", implode(" ", $attr));
        $template->assign("attr_list", $fieldVars["attr"]);
    }

    protected function assignFormTypeValues($template, $formFieldConfig, $formFieldView)
    {
        $formFieldType = $formFieldConfig->getType()->getInnerType();

        /* access to choices */
        if ($formFieldType instanceof ChoiceType) {
            $template->assign("choices", $formFieldView->vars['choices']);
        }

        /* access to collections */
        if ($formFieldType instanceof CollectionType) {
            if ( true === $formFieldConfig->getOption('prototype') ) {

            } else {
                /* access to choices */
                if (isset($formFieldView->vars['choices'])) {
                    $template->assign("choices", $formFieldView->vars['choices']);

                }
            }
        }

        /* access to thelia type */
        if ($formFieldType instanceof TheliaType) {
            $template->assign("formType", $formFieldView->vars['type']);

            switch ($formFieldView->vars['type']) {
                case "choice":
                    if (!isset($formFieldView->vars['options']['choices']) || !is_array($formFieldView->vars['options']['choices'])) {
                        //throw new
                    }
                    $choices = array();
                    foreach ($formFieldView->vars['options']['choices'] as $value => $choice) {
                        $choices[] = new ChoiceView($value, $value, $choice);
                    }
                    $template->assign("choices", $choices);
                    break;
            }
        }
    }

    public function renderFormField($params, $content, \Smarty_Internal_Template $template, &$repeat)
    {
        if ($repeat) {

            $formFieldView = $this->getFormFieldView($params);
            $formFieldConfig = $this->getFormFieldConfig($params);

            $this->assignFormTypeValues($template, $formFieldConfig, $formFieldView);

            $value = $formFieldView->vars["value"];

            $key = $this->getParam($params, 'value_key', null);

            // We (may) have a collection
            if ($key !== null) {

                // Force array
                if (! is_array($value)) $value = array();

                // If the field is not found, use an empty value
                $val = array_key_exists($key, $value) ? $value[$key] : '';

                $name = sprintf("%s[%s]", $formFieldView->vars["full_name"], $key);

                $val = $value[$key];

                $this->assignFieldValues($template, $name, $val, $formFieldView->vars, count($formFieldView->children));
            } else {
                $this->assignFieldValues($template, $formFieldView->vars["full_name"], $formFieldView->vars["value"], $formFieldView->vars);
            }

            $formFieldView->setRendered();
        } else {
            return $content;
        }
    }

    public function renderTaggedFormFields($params, $content, \Smarty_Internal_Template $template, &$repeat)
    {
        if (null === $content) {
            self::$taggedFieldsStack = $this->getFormFieldsFromTag($params);
            self::$taggedFieldsStackPosition = 0;
        } else {
            self::$taggedFieldsStackPosition++;
        }

        if (isset(self::$taggedFieldsStack[self::$taggedFieldsStackPosition])) {
            $this->assignFieldValues(
                $template,
                self::$taggedFieldsStack[self::$taggedFieldsStackPosition]['view']->vars["full_name"],
                self::$taggedFieldsStack[self::$taggedFieldsStackPosition]['view']->vars["value"],
                self::$taggedFieldsStack[self::$taggedFieldsStackPosition]['view']->vars
            );

            $this->assignFormTypeValues($template, self::$taggedFieldsStack[self::$taggedFieldsStackPosition]['config'], self::$taggedFieldsStack[self::$taggedFieldsStackPosition]['view']);

            self::$taggedFieldsStack[self::$taggedFieldsStackPosition]['view']->setRendered();

            $repeat = true;
        }

        if (! $repeat) {
            self::$taggedFieldsStack = null;
            self::$taggedFieldsStackPosition = null;
        }

        if (null !== $content) {
            return $content;
        }
    }

    public function renderHiddenFormField($params, \Smarty_Internal_Template $template)
    {
        $attrFormat = '%s="%s"';
        $field = '<input type="hidden" name="%s" value="%s" %s>';

        $instance = $this->getInstanceFromParams($params);

        $formView = $instance->getView();

        $return = "";

        foreach ($formView->getIterator() as $row) {
            if ($this->isHidden($row) && $row->isRendered() === false) {
                $attributeList = array();
                if (isset($row->vars["attr"])) {
                    foreach ($row->vars["attr"] as $attrKey => $attrValue) {
                        $attributeList[] = sprintf($attrFormat, $attrKey, $attrValue);
                    }
                }
                $return .= sprintf($field, $row->vars["full_name"], $row->vars["value"], implode(' ', $attributeList));
            }
        }

        return $return;
    }

    public function formEnctype($params, \Smarty_Internal_Template $template)
    {
        $instance = $this->getInstanceFromParams($params);

        $formView = $instance->getView();

        if ($formView->vars["multipart"]) {
            return sprintf('%s="%s"', "enctype", "multipart/form-data");
        }
    }

    public function formError($params, $content, \Smarty_Internal_Template $template, &$repeat)
    {
        $formFieldView = $this->getFormFieldView($params);

        $errors = $formFieldView->vars["errors"];

        if (empty($errors)) {
            return "";
        }

        if ($repeat) {
            $this->assignFieldErrorVars($template, $errors);
        } else {
            return $content;
        }
    }

    protected function assignFieldErrorVars(\Smarty_Internal_Template $template, array $errors)
    {
        $template->assign("message", $errors[0]->getMessage());
        $template->assign("parameters", $errors[0]->getMessageParameters());
        $template->assign("pluralization", $errors[0]->getMessagePluralization());
    }

    protected function isHidden(FormView $formView)
    {
        return array_search("hidden", $formView->vars["block_prefixes"]);
    }

    protected function getFormFieldView($params)
    {
        $instance = $this->getInstanceFromParams($params);

        $fieldName = $this->getParam($params, 'field');

        if (null == $fieldName) throw new \InvalidArgumentException("'field' parameter is missing");

        if (empty($instance->getView()[$fieldName])) throw new \InvalidArgumentException(
                sprintf("Field name '%s' not found in form %s", $fieldName, $instance->getName()));

        return $instance->getView()[$fieldName];
    }

    protected function getFormFieldsFromTag($params)
    {
        $instance = $this->getInstanceFromParams($params);

        $tag = $this->getParam($params, 'tag');

        if (null == $tag)
            throw new \InvalidArgumentException("'tag' parameter is missing");

        $viewList = array();
        foreach ($instance->getView() as $view) {
            if (isset($view->vars['attr']['tag']) && $tag == $view->vars['attr']['tag']) {
                $fieldData = $instance->getForm()->all()[$view->vars['name']];
                $viewList[] = array(
                    'view' => $view,
                    'config' => $fieldData->getConfig(),
                );
            }
        }

        return $viewList;
    }

    protected function getFormFieldConfig($params)
    {
        $instance = $this->getInstanceFromParams($params);

        $fieldName = $this->getParam($params, 'field');

        if (null == $fieldName) {
            throw new \InvalidArgumentException("'field' parameter is missing");
        }

        $fieldData = $instance->getForm()->all()[$fieldName];

        if (empty( $fieldData )) {
            throw new \InvalidArgumentException(sprintf("Field name '%s' not found in form %s children", $fieldName, $instance->getName()));
        }

        return $fieldData->getConfig();
    }

    protected function getInstanceFromParams($params)
    {
        $instance = $this->getParam($params, 'form');

        if (null == $instance) {
            throw new \InvalidArgumentException("Missing 'form' parameter in form arguments");
        }

        if (!$instance instanceof \Thelia\Form\BaseForm) {
            throw new \InvalidArgumentException(
                    sprintf(
                            "form parameter in form_field block must be an instance of
                \Thelia\Form\BaseForm, instance of %s found", get_class($instance)));
        }

        return $instance;
    }

    /**
     * @return an array of SmartyPluginDescriptor
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor("block", "form", $this, "generateForm"),
            new SmartyPluginDescriptor("block", "form_field", $this, "renderFormField"),
            new SmartyPluginDescriptor("block", "form_tagged_fields", $this, "renderTaggedFormFields"),
            new SmartyPluginDescriptor("function", "form_hidden_fields", $this, "renderHiddenFormField"),
            new SmartyPluginDescriptor("function", "form_enctype", $this, "formEnctype"),
            new SmartyPluginDescriptor("block", "form_error", $this, "formError")
        );
    }
}
