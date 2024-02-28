<?php
/**
 * Youtube Gallery Joomla! Module
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/


defined('_JEXEC') or die('Restricted access');

$path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_youtubegallery' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'youtubegallery' . DIRECTORY_SEPARATOR;
require_once($path . 'loader.php');
YGLoadClasses();

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use YouTubeGallery\Helper;

$listId = (int)$params->get('listid');

//Get Theme
if (CustomTables\Environment::check_user_agent('mobile')) {
    //Use Mobile Theme if set.
    $themeId = (int)$params->get('mobilethemeid');
    if ($themeId == 0)
        $themeId = (int)$params->get('themeid');
} else
    $themeId = (int)$params->get('themeid');

$align = '';

if ($listId != 0 and $themeId != 0) {
    $ygDB = new YouTubeGalleryDB;

    $videoListAndThemeFound = true;
    $videoListAndThemeFound=$ygDB->getVideoListTableRow($listId);

    if (!$videoListAndThemeFound)
        JFactory::getApplication()->enqueueMessage(JText::_('MOD_YOUTUBEGALLERY_ERROR_VIDEOLIST_NOT_SET'), 'error');

    $videoListAndThemeFound=$ygDB->getThemeTableRow($themeId);
    if(!$videoListAndThemeFound)
        JFactory::getApplication()->enqueueMessage(JText::_('MOD_YOUTUBEGALLERY_ERROR_THEME_NOT_SET'), 'error');

    $youtubeGalleryCode = '';

    if ($videoListAndThemeFound) {

        $firstVideo = '';
        $total_number_of_rows = 0;
        $ygDB->update_playlist();

        $videoId = JFactory::getApplication()->input->getCmd('videoid', '');
        if (!isset($videoId) or $videoId == '') {
            $video = JFactory::getApplication()->input->getVar('video', '');
            $video = preg_replace('/[^a-zA-Z0-9-_]+/', '', $video);

            if ($video != '')
                $videoId = YouTubeGalleryDB::getVideoIDbyAlias($video);
        }

        if ($ygDB->theme_row->es_playvideo == 1 and $videoId != '')
            $ygDB->theme_row->es_autoplay = 1;

        $videoIdNew = $videoId;
        $jinput = JFactory::getApplication()->input;
        if ($jinput->getInt('yg_api') == 1) {
            $videoList = $ygDB->getVideoList_FromCache_From_Table($videoIdNew, $total_number_of_rows, false);
            $result = json_encode($videoList);

            if (ob_get_contents())
                ob_end_clean();

            header('Content-Disposition: attachment; filename="youtubegallery_api.json"');
            header('Content-Type: application/json; charset=utf-8');
            header("Pragma: no-cache");
            header("Expires: 0");

            echo $result;
            die;

            return '';
        } else {
            $videoList = $ygDB->getVideoList_FromCache_From_Table($videoIdNew, $total_number_of_rows);
        }

        $custom_itemid = (int)$params->get('customitemid');
        $renderer = new YouTubeGalleryRenderer;
        $galleryModule = $renderer->render(
            $videoList,
            $ygDB->videoListRow,
            $ygDB->theme_row,
            $total_number_of_rows,
            $videoId,
            $custom_itemid
        );

        if ($params->get('allowcontentplugins')) {

	        $version_object = new Version;
	        $version = (int)$version_object->getShortVersion();

	        if ($version < 4) {
		        $o = new stdClass();
		        $o->text = $galleryModule;
		        $dispatcher = JDispatcher::getInstance();
		        JPluginHelper::importPlugin('content');
		        $r = $dispatcher->trigger('onContentPrepare', array('com_content.article', &$o, &$params_, 0));
		        $galleryModule = $o->text;
	        }
			else {
				$mainframe = Factory::getApplication();
				$content_params = $mainframe->getParams('com_content');
				$galleryModule = \Joomla\CMS\HTML\Helpers\Content::prepare($galleryModule, $content_params);
			}
        }

        $align = $params->get('galleryalign');

        switch ($align) {
            case 'left' :
                $youtubeGalleryCode .= '<div style="float:left;position:relative;">' . $galleryModule . '</div>';
                break;

            case 'center' :
                $youtubeGalleryCode .= '<div style="width:' . $ygDB->theme_row->width . 'px;margin-left:auto;margin-right:auto;position:relative;">' . $galleryModule . '</div>';
                break;

            case 'right' :
                $youtubeGalleryCode .= '<div style="float:right;position:relative;">' . $galleryModule . '</div>';
                break;

            default :
                $youtubeGalleryCode .= $galleryModule;
                break;

        }//switch($align)
    } else {
        //JFactory::getApplication()->enqueueMessage('Youtube Gallery: Video List and Theme not found.', 'error');
        //echo '<p style="background-color:red;color:white;">Youtube Gallery: Video List and Theme not found.</p>';
    }

    echo $youtubeGalleryCode;

} else
    echo '<p>Video list or Theme not selected</p>';
