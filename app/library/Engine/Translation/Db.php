<?php

/**
 * PhalconEye
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to lantian.ivan@gmail.com so we can send you a copy immediately.
 *
 */

use Phalcon\Translate\Adapter,
    Phalcon\Translate\AdapterInterface,
    Phalcon\Translate\Exception;

class Translation_Db implements AdapterInterface
{
    /**
     * @var \Phalcon\Db\Adapter\Pdo
     */
    protected $_db;

    /**
     * @var Language
     */
    protected $_locale;

    /**
     * Translation_Db constructor
     *
     * @throws Exception
     * @param array $options
     */
    public function __construct($options)
    {
        $this->_db = $options['db'];

        $this->_locale = Language::find(array(
            'conditions' => 'locale = :locale:',
            'bind' => (array(
                "locale" => $options['locale']
            )),
            'bindTypes' => (array(
                "locale" => \Phalcon\Db\Column::BIND_PARAM_STR
            ))
        ))->getFirst();

        if (!$this->_locale) {
            $this->_locale = Language::findFirst("locale = 'en'");
        }
    }

    /**
     * Returns the translation string of the given key
     *
     * @param   string $translateKey
     * @param   array $placeholders
     * @return  string
     */
    public function _($translateKey, $placeholders = null)
    {
        return $this->query($translateKey, $placeholders);
    }


    /**
     * Returns the translation related to the given key
     *
     * @param    string $index
     * @param    array $placeholders
     * @return    string
     */
    public function query($index, $placeholders = null)
    {
        if (!$this->_locale || empty($index))
            return $index;

        // cleanup
        $index = preg_replace('~[\r\n]+~', '',$index);

        $translation = $this->get($index);

        if (!$translation) {
            // remember this translation
            $translation = new LanguageTranslation();
            $translation->setLanguageId($this->_locale->getId());
            $translation->setOriginal($index);
            $translation->setTranslated($index);
            $translation->save();

            return $index;
        }

        $translated = $translation->getTranslated();

        if ($placeholders == null) {
            return $translated;
        }

        if (is_array($placeholders)) {
            foreach ($placeholders as $key => $value) {
                $translated = str_replace('%' . $key . '%', $value, $translated);
            }

        }

        return $translated;
    }

    /**
     * Check whether is defined a translation key in the internal array
     *
     * @param     string $index
     * @return    bool
     */
    public function exists($index)
    {
        return $this->get($index) !== null;
    }


    private function get($index){
        return LanguageTranslation::find(array(
            'conditions' => 'original = :content: AND language_id = :id:',
            'bind' => (array(
                "content" => $index,
                "id" => $this->_locale->getId()
            )),
            'bindTypes' => (array(
                "content" => \Phalcon\Db\Column::BIND_PARAM_STR,
                "id" => \Phalcon\Db\Column::BIND_PARAM_INT
            ))
        ))->getFirst();
    }

}