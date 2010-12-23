<?php

/**
 * Tagger Plugin for Wolf CMS <http://thehub.silentworks.co.uk/plugins/frog-cms/tagger.html>
 * Alternate Mirror site <http://www.tbeckett.net/articles/plugins/tagger.xhtml>
 * Copyright (C) 2008 - 2010 Andrew Smith <a.smith@silentworks.co.uk>
 * Copyright (C) 2008 - 2010 Tyler Beckett <tyler@tbeckett.net>
 * 
 * Dual licensed under the MIT (license/mit-license.txt)
 * and GPL (license/gpl-license.txt) licenses.
 */

class Tagger
{
    public function __construct(&$page, $params)
    {
        $this->page =& $page;
        $this->params = $params;

        switch(count($params))
        {
            case 0: break;
            case 1:
                $this->pagesByTag($params);
            break;
            default:
                page_not_found();
        }
    }

    public function tag($params = false)
    {
        if(!$params) $params = $this->params;

        return $params[0];
    }

    public function pagesByTag($params = false)
	{
		$pdoConn = Record::getConnection();
		
		// Count rows in table
		$query_count = "SELECT count(*) FROM ".TABLE_PREFIX."page;";
		$qc1 = $pdoConn->query($query_count);

		if($qc1->fetchColumn() > 0) {
			// Execute real query
			$query1 = "SELECT parent_id, id, slug FROM ".TABLE_PREFIX."page AS page ORDER BY page.position ASC;";
			$pdo1 = $pdoConn->prepare($query1);
			$pdo1->execute();
			
			while($article = $pdo1->fetchObject()) {
				$archive[] = array(
					'slug' => $article->slug,
					'pid' => $article->parent_id,
					'id' => $article->id);
			}
		}

		$url = array();
		foreach($archive as $parent) {
			$url[$parent['id']] = $parent['slug'].'/';
			$pid = $parent['pid'];
			while ($pid != 0) {
				$query2 = "SELECT parent_id, slug FROM ".TABLE_PREFIX."page AS page WHERE page.id = '$pid' ORDER BY page.position ASC;";
				$pdo2 = $pdoConn->prepare($query2);
				$pdo2->execute();
				$query = $pdo2->fetchObject();
				$pid = $query->parent_id;
				$url[$parent['id']] = $query->slug.'/'.$url[$parent['id']];
			}
			// Trims the initial ' / ' off the front of the url
			$url[$parent['id']] = substr($url[$parent['id']], 1);
		}

		if(!$params) $params = $this->params;

		$tagged = array();

		$tag_unslugified = unslugify(isset($params[0]) ? $params[0] : NULL);
		$tag = isset($params[0]) ? $params[0] : NULL;
		
		// Count rows in table
		$sql_count = "SELECT count(*) FROM ".TABLE_PREFIX."page AS page, ".TABLE_PREFIX."page_tag AS page_tag, ".TABLE_PREFIX."tag AS tag WHERE page.id = page_tag.page_id AND page_tag.tag_id = tag.id AND ((tag.name = '$tag') OR (tag.name = '$tag_unslugified')) AND page.status_id != ".Page::STATUS_HIDDEN." AND page.status_id != ".Page::STATUS_DRAFT." ORDER BY page.created_on DESC";
		$qc2 = $pdoConn->query($sql_count);

		if($qc2->fetchColumn() > 0) {
			
			$query3 = "SELECT * FROM ".TABLE_PREFIX."page AS page, ".TABLE_PREFIX."page_tag AS page_tag, ".TABLE_PREFIX."tag AS tag WHERE page.id = page_tag.page_id AND page_tag.tag_id = tag.id AND ((tag.name = '$tag') OR (tag.name = '$tag_unslugified')) AND page.status_id != ".Page::STATUS_HIDDEN." AND page.status_id != ".Page::STATUS_DRAFT." ORDER BY page.created_on DESC";
			$pdo3 = $pdoConn->prepare($query3);
			$pdo3->execute();
			
			while ($content = $pdo3->fetchObject()) {
				if(isset($url[$content->parent_id])) {
					$tagged[BASE_URL . $url[$content->parent_id] . $content->slug . URL_SUFFIX] = $content->title;
				} else {
					$tagged[BASE_URL . $content->slug . URL_SUFFIX] = $content->title;
				}
			}
		} else return false;

		return $tagged;
	}
}

/**
 * Internal Function
 * Unslugify the tag to make it human readable.
 *
 * @since 1.0.1
 * @param string $string
 */
function unslugify($string) {
	return str_replace('-', ' ', $string);
}