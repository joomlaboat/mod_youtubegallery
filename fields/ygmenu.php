<?php
/**
 * Youtubegallery - Search Module for Joomla! 2.5
 * @version 5.0.01.0.0
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @license GNU/GPL
 **/

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * YoutubeGallery Form Field class for the Youtube Gallery component
 */
class JFormFieldYGMenu extends JFormFieldList
{
    /**
     * The field type.
     *
     * @var         string
     */
    protected $type = 'YGMenu';

    /**
     * Method to get a list of options for a list input.
     *
     * @return      array           An array of JHtml options.
     */
    protected function getOptions()
    {
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id,title');
        $query->from('#__menu');
        $query->where('published');
        $query->where('client_id=0');
        $query->where('INSTR(link,"option=com_youtubegallery")');

        $db->setQuery((string)$query);
        $messages = $db->loadObjectList();
        $options = array();
        if ($messages) {
            $options[] = HTMLHelper::_('select.option', 0, " - " . Text::_('MOD_YOUTUBEGALLERY_SELECT_MENU'));
            foreach ($messages as $message) {
                $options[] = HTMLHelper::_('select.option', $message->id, $message->title);

            }
        }
        $options = array_merge(parent::getOptions(), $options);
        return $options;
    }
}
