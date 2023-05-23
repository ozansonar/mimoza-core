<?php

namespace OS\MimozaCore;

use PDO;

class SiteManager
{
	/**
	 * Database class
	 *
	 * @var Database
	 */
	private Database $database;

	/**
	 * Functions class
	 *
	 * @var Functions
	 */
	private Functions $functions;

	/**
	 * Core class
	 *
	 * @var Core
	 */
	private Core $system;

	/**
	 * Constructor
	 *
	 * @param Database $database
	 */
	public function __construct(Database $database)
	{
		$this->database = $database;
		$this->functions = new Functions();
		$this->system = new Core();
	}

	/**
	 * It's return header navbar
	 *
	 * @return string
	 */
	public function getHeaderNavbar(): string
	{
		$navbar = "";
		/** @var Database $db */
		$data_query = $this->database::$db->prepare("SELECT * FROM menu WHERE lang=:lang AND show_type IN(1,3) AND menu_type=1 AND status=1 AND deleted=0 ORDER BY show_order ASC");
		$data_query->bindParam(":lang", $_SESSION["lang"], PDO::PARAM_STR);
		$data_query->execute();
		$data = $data_query->fetchAll(PDO::FETCH_OBJ);
		foreach ($data as $row) {
			//alt menu var mı
			$data_query = $this->database::$db->prepare("SELECT * FROM menu WHERE lang=:lang AND top_id=:top_id AND status=1 AND deleted=0 ORDER BY show_order ASC");
			$data_query->bindParam(":lang", $_SESSION["lang"], PDO::PARAM_STR);
			$data_query->bindParam(":top_id", $row->id, PDO::PARAM_INT);
			$data_query->execute();
			$sub_nav_data = $data_query->fetchAll(PDO::FETCH_OBJ);
			if (!empty($sub_nav_data)) {
				$navbar .= '<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        ' . $row->name . '
                    </a>
                     <ul class="dropdown-menu" aria-labelledby="navbarDropdown">';
				foreach ($sub_nav_data as $sub) {
					$navbar .= '<li>
                            <a class="dropdown-item" href="' . ((int)$sub->redirect === 1 ? $sub->redirect_link : $this->system->url($sub->link)) . '" target="' . ((int)$sub->redirect === 1 && (int)$sub->redirect_open_type === 1 ? "_blank" : "_self") . '">
                                ' . $sub->name . '
                            </a>
                        </li>';
				}
				$navbar .= '
                        </ul>
                    </li>';
			} else {
				$navbar .= '<li class="nav-item">
                    <a class="nav-link" href="' . ((int)$row->redirect === 1 ? $row->redirect_link : $this->system->url($row->link)) . '" target="' . ((int)$row->redirect === 1 && (int)$row->redirect_open_type === 1 ? "_blank" : "_self") . '">
                        ' . $row->name . '
                    </a>
                </li>';
			}
		}
		return $navbar;
	}

	/**
	 * It's return footer menu
	 *
	 * @return string
	 */
	public function getFooterNavbar(): string
	{
		/** @var Database $db */
		$query = $this->database::$db->prepare("SELECT * FROM menu WHERE show_type IN(2,3) AND lang=:lang AND menu_type=1 AND status=1 AND deleted=0 ORDER BY show_order ASC");
		$query->bindParam(":lang", $_SESSION["lang"], PDO::PARAM_STR);
		$query->execute();
		$data = $query->fetchAll(PDO::FETCH_OBJ);
		$result = "";
		foreach ($data as $row) {
			$link = $row->link;
			if ((int)$row->redirect === 1) {
				$link = $row->redirect_link;
			}
			$result .= '<li class="mb-2"><i class="fas fa-angle-right"></i><a href="' . $link . '" target="' . ($row->redirect_open_type == 1 ? "_blank" : null) . '" class="link-hover-style-1 ms-1">' . $row->name . '</a></li>';
		}
		return $result;
	}

	/**
	 * It's return 404 page navbar
	 *
	 * @return string
	 */
	public function get404Navbar(): string
	{
		$data = $this->database::selectQuery("menu", array(
			"lang" => $_SESSION["lang"],
			"menu_type" => 1,
			"status" => 1,
			"deleted" => 0,
		), false, null, 5, "show_order ASC");
		$result = "";
		foreach ($data as $row) {
			$link = $row->link;
			if ((int)$row->redirect === 1) {
				$link = $row->redirect_link;
			}
			$result .= '<li class="nav-item"><a class="nav-link" target="' . ((int)$row->redirect_open_type === 1 ? "_blank" : null) . '" href="' . $link . '">' . $row->name . '</a></li>';
		}
		return $result;
	}

	/**
	 * It's return category
	 *
	 * @param int $id Category id
	 * @return array
	 *
	 */
	public function getCategory(int $id): array
	{
		/** @var Database $db */
		$database = $this->database::$db;
		$query = $database->prepare("SELECT * FROM content_categories WHERE id=:id AND deleted=0 AND status=1 LIMIT 0,1");
		$query->bindParam(":id", $id, PDO::PARAM_INT);
		$query->execute();
		$queryData = $query->fetch(PDO::FETCH_OBJ);
		$countQuery = $query->rowCount();
		if ($queryData->lang !== $_SESSION["lang"]) {
			$query = $database->prepare("SELECT * FROM content_categories WHERE lang_id=:lid AND deleted=0 AND lang=:lang AND status=1 LIMIT 0,1");
			$query->bindParam(":lid", $queryData->lang_id, PDO::PARAM_STR);
			$query->bindParam(":lang", $_SESSION["lang"], PDO::PARAM_STR);
			$query->execute();
			$queryData = $query->fetch(PDO::FETCH_OBJ);
			$countQuery = $query->rowCount();
		}
		return [$countQuery, $queryData];
	}

	/**
	 * It's return content url
	 *
	 * @param object $content
	 * @return string
	 *
	 */
	public function createContentUrl(object $content): string
	{
		global $settings;
		[$countQuery, $category] = $this->getCategory($content->cat_id);
		return $this->system->url($settings->{"content_prefix_" . $_SESSION["lang"]} . "/" . $category->link . '-'.$category->id . "/" . $content->link . "-" . $content->id);
	}

    /**
     * It's return content url
     *
     * @param object $content
     * @return string
     *
     */
    public function createContentLinkNoUrl(object $content): string
    {
        global $settings;
        [$countQuery, $category] = $this->getCategory($content->cat_id);
        return $settings->{"content_prefix_" . $_SESSION["lang"]} . "/" . $category->link . '-'.$category->id . "/" . $content->link . "-" . $content->id;
    }

    /**
     * It's return content url
     *
     * @param object $category
     * @return string
     *
     */
    public function createCategoryUrl(object $category, string $lang = null): string
    {
        global $settings;
        if(empty($lang)){
            $lang = $_SESSION["lang"];
        }
        if((int)$settings->link_sort_lang === 1){
            return $this->system->urlWithoutLanguage($lang.'/'.$settings->{"content_prefix_" . $lang} . "/" . $category->link . '-'.$category->id);
        }else{
            return $this->system->urlWithoutLanguage($settings->{"content_prefix_" . $lang} . "/" .$category->link . '-'.$category->id);
        }
    }

    /**
     * It's return content url
     *
     * @param object $category
     * @return string
     *
     */
    public function createCategoryLinkNoUrl(object $category, string $lang = null): string
    {
        global $settings;
        if(empty($lang)){
            $lang = $_SESSION["lang"];
        }
        return $settings->{"content_prefix_" . $lang} . "/" .$category->link . '-'.$category->id;
    }

	/**
	 * $table tablosunda $columun kolununnda $data yı arar ve var olan değerleri döndürür
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $data
	 * @return int
	 */
	public function uniqData(string $table, string $column, string $data): int
	{
		$data = $this->functions->cleaner($data);
		/** @var Database $db */
		$query = $this->database::$db->prepare("SELECT " . $column . " FROM " . $table . " WHERE deleted=0 AND " . $column . "=:" . $column . "");
		$query->bindParam(":" . $column . "", $data, PDO::PARAM_STR);
		$query->execute();
		return $query->rowCount();
	}

	/**
	 * $table tablosunda $columun kolununnda idsi $id den hariç $data yı aray ve var olan değerleri döndürür
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $data
	 * @param int $id
	 * @return int
	 */
	public function uniqDataWithoutThis(string $table, string $column, string $data, int $id): int
	{
		$data = $this->functions->cleaner($data);
		/** @var Database $db */
		$query = $this->database::$db->prepare("SELECT * FROM " . $table . " WHERE " . $column . "=:" . $column . " AND id!=:id AND deleted=0");
		$query->bindParam(":" . $column . "", $data, PDO::PARAM_STR);
		$query->bindParam(":id", $id, PDO::PARAM_INT);
		$query->execute();
		return $query->rowCount();
	}

	public function uniqDataControl(string $table, string $column, string $data): int
	{
		/** @var Database $db */
		$query = $this->database::$db->prepare("SELECT * FROM " . $table . " WHERE " . $column . "=:" . $column . " AND deleted = 0");
		$query->bindParam(":" . $column . "", $data, PDO::PARAM_STR);
		$query->execute();
		return $query->rowCount();
	}

	/**
	 * Ana indexde sayfaları konrol etmek için kullanırız
	 *
	 * @param string $link
	 * @return mixed
	 */
	public function pageControl(string $link)
	{
		$link = $this->functions->cleaner($link);
		/** @var Database $db */
		$database = $this->database::$db;
		$query = $database->prepare("SELECT * FROM page WHERE link=:link AND deleted=0 AND status=1 LIMIT 0,1");
		$query->bindParam(":link", $link, PDO::PARAM_STR);
		$query->execute();
		return $query->fetch(PDO::FETCH_OBJ);
	}

	/**
	 * Gönderilen linke göre olan datayı gönderir
	 *
	 * @param string $link
	 * @return mixed
	 */
	public function customFileUrl(string $link)
	{
		$link = $this->functions->cleaner($link);
		/** @var Database $db */
		$database = $this->database::$db;
		$query = $database->prepare("SELECT * FROM file_url WHERE lang=:lang AND url=:link AND deleted=0 AND status=1 LIMIT 0,1");
		$query->bindParam(":lang", $_SESSION["lang"], PDO::PARAM_STR);
		$query->bindParam(":link", $link, PDO::PARAM_STR);
		$query->execute();
		return $query->fetch(PDO::FETCH_OBJ);
	}

	/**
	 * Galeriye ait videoları getirir
	 *
	 * @param int $id
	 * @return array
	 */
	public function getGalleryVideos(int $id): array
	{
		/** @var Database $db */
		$database = $this->database::$db;
		$query = $database->prepare("SELECT * FROM youtube_videos WHERE gallery_id=:id AND status=1 AND deleted=0 ORDER BY show_order ASC");
		$query->bindParam(":id", $id, PDO::PARAM_STR);
		$query->execute();
		return [$query->rowCount(), $query->fetchAll(PDO::FETCH_OBJ)];
	}

	/**
	 * Gönderilen $id ye ait galeriyi ve ona ait resimleri getirir
	 *
	 * @param int $id
	 * @return array
	 */
	public function galleryGet(int $id): array
	{
		/** @var Database $db */
		$dbs = $this->database::$db;
		$dataRow = array();
		// Alt galerisi bulnan galeri
		$resultType = 1;
		$query = $dbs->prepare("SELECT * FROM gallery WHERE top_id=:id AND status=1 AND deleted=0");
		$query->bindParam(":id", $id, PDO::PARAM_STR);
		$query->execute();
		$queryData = $query->fetchAll(PDO::FETCH_OBJ);

		if (empty($queryData)) {
			// Alt galerisi olmayan galeri yani resimler var
			$resultType = 2;
			$query = $dbs->prepare("SELECT * FROM gallery WHERE id=:id AND status=1 AND deleted=0");
			$query->bindParam(":id", $id, PDO::PARAM_STR);
			$query->execute();
			$queryData = $query->fetch(PDO::FETCH_OBJ);

			if (!empty($queryData)) {
				$dataQuery = $dbs->prepare("SELECT * FROM gallery_image WHERE gallery_id=:g_id AND status=1 AND deleted=0 ORDER BY id DESC");
				$dataQuery->bindParam(":g_id", $id, PDO::PARAM_INT);
				$dataQuery->execute();
				$dataResult = $dataQuery->fetchAll(PDO::FETCH_OBJ);
				if (!empty($dataResult)) {
					$dataRow = $dataResult;
				}
			}
		}
		return array($resultType, $queryData, $dataRow);
	}

	/**
	 * Gönderilen deger göre mail alamyı isteyen kisileri dönderir
	 *
	 * @param int $rank
	 * @return array
	 */
	public function getUsersSendMail(int $rank): array
	{
		/** @var Database $db */
		$q = $this->database::$db->prepare("SELECT * FROM users WHERE rank=:rank AND send_mail=1 AND email_verify=1 AND status=1 AND deleted=0");
		$q->bindParam(":rank", $rank, PDO::PARAM_INT);
		$q->execute();
		return array($q->rowCount(), $q->fetchAll(PDO::FETCH_OBJ));
	}

	/**
	 * If there is a guarded page in frontend. Please use this
	 *
	 * @param int $type
	 * @param int $id
	 * @param int $dataType
	 * @return array
	 */
	public function pagePermission(int $type, int $id, int $dataType = 5): array
	{
		/** @var Database $db */
		$query = $this->database::$db->prepare("SELECT * FROM pagePermission WHERE content_type=:c_type AND content_id=:c_id AND status=1 AND deleted=0 ORDER BY id ASC");
		$query->bindParam(":c_type", $type, PDO::PARAM_INT);
		$query->bindParam(":c_id", $id, PDO::PARAM_INT);
		$query->execute();
		return array($query->rowCount(), $query->fetchAll($dataType));
	}

	/**
	 * It's return default language
	 *
	 * @return mixed
	 */
	public function defaultLanguage()
	{
		return $this->database::selectQuery("lang", array(
			"default_lang" => 1,
			"status" => 1,
			"deleted" => 0,
		), true);
	}

	/**
	 *
	 * @param string $table
	 * @param int $id
	 * @return bool
	 */
	public function multipleLanguageDataDelete(string $table, int $id): bool
	{
		/** @var Database $db */
		$detail_query = $this->database::$db->prepare("SELECT * FROM " . $table . " WHERE deleted=0 AND id=:id");
		$detail_query->bindParam(":id", $id, PDO::PARAM_INT);
		$detail_query->execute();
		$detail = $detail_query->fetch(PDO::FETCH_OBJ);

		if (!empty($detail)) {
			$delete = $this->database::$db->prepare("UPDATE " . $table . " SET deleted=1 WHERE lang_id=:lang_id");
			$delete->bindParam(':lang_id', $detail->lang_id, PDO::PARAM_INT);
			$delete->execute();
			return true;
		}

		return false;
	}

	/**
	 *
	 * @param int $id
	 * @return bool
	 */
	public function getDefaultLangNotId(int $id = 0): bool
	{
		$sql_add = "";
		if ($id > 0) {
			$sql_add = " id!=:id AND ";
		}
		/** @var Database $db */
		$query = $this->database::$db->prepare("SELECT * FROM lang WHERE $sql_add default_lang=1 AND status=1 AND deleted=0");
		if ($id > 0) {
			$query->bindParam(":id", $id, PDO::PARAM_INT);
		}
		$query->execute();
		$count = (int)$query->rowCount();
		return $count === 1;
	}

	/**
	 *
	 * @param int $id
	 */
	public function defaultLanguageReset(int $id): void
	{
		/** @var Database $db */
		$query = $this->database::$db->prepare("UPDATE lang SET default_lang=2,form_validate=2 WHERE id!=:id");
		$query->bindParam(':id', $id, PDO::PARAM_INT);
		$query->execute();
	}

	/**
	 *
	 * @param int $id
	 * @return false
	 */
	public function getMailTemplate(int $id)
	{
		/** @var Database $db */
		$query = $this->database::$db->prepare("SELECT * FROM email_template WHERE id=:id AND deleted=0 AND status=1");
		$query->bindParam(":id", $id, PDO::PARAM_INT);
		$query->execute();
		$countQuery = $query->rowCount();
		$queryData = $query->fetch(PDO::FETCH_OBJ);
		if ($countQuery === 1) {
			//lang id yi bulduk şimdi dile ait içeri çekip dönelim
			$query = $this->database::$db->prepare("SELECT * FROM email_template WHERE lang_id=:id AND lang=:lang AND deleted=0 AND status=1");
			$query->bindParam(":id", $queryData->lang_id, PDO::PARAM_INT);
			$query->bindParam(":lang", $_SESSION["lang"], PDO::PARAM_STR);
			$query->execute();
			$countQuery = $query->rowCount();
			if ($countQuery === 1) {
				return $query->fetch(PDO::FETCH_OBJ);
			}
			return false;
		}
		return false;
	}

	public function getDefaultLanguageCode()
	{
		/** @var Database $db */
		$defaultLanguage = $this->database::selectQuery('lang',[ 'default_lang'=> 1, 'deleted'=>0, 'status'=>1],true);
		return $defaultLanguage->short_lang;
	}

    /**
     * Gönderilen tablodaki resmi siler
     * @param string $lang
     * @param int $id
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function imageDelete(string $lang, int $id,string $table, string $column = "img"):bool
    {
        return $this->database::update($table,[$column=>null],["id"=>$id,"lang"=>$lang]);
    }

    public function imageDeleteNoLang(int $id,string $table, string $column = "img"):bool
    {
        return $this->database::update($table,[$column=>null],["id"=>$id]);
    }

    /**
     * $langId ye ait tüm içeriklerin linki verir
     * @param $langId
     * @return array
     */
    public function getOrtherLanguageContent($langId):array
    {
        $query = $this->database::query('SELECT c.id,c.link,c.lang,c.lang_id,c.cat_id,cc.link as cc_link FROM content c INNER JOIN content_categories cc ON cc.id=c.cat_id WHERE c.lang_id=:lang_id AND c.status=1 AND c.deleted=0');
        $query->bindParam(':lang_id',$langId);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        if(!empty($data)){
            foreach ($data as $row){
                $result[$row->lang] = $this->system->urlWithoutLanguage($row->lang.'/'.$this->getPrefix('content',$row->lang).'/'.$row->cc_link.'-'.$row->cat_id.'/'.$row->link.'-'.$row->id);
            }
        }
        return $result;
    }

    /**
     * $langId ye ait tüm içeriklerin linki verir
     * @param $langId
     * @return array
     */
    public function getOrtherLanguageContentCategories($langId):array
    {
        $query = $this->database::query('SELECT cc.id,cc.link,cc.lang,cc.lang_id FROM content_categories cc WHERE cc.lang_id=:lang_id AND cc.status=1 AND cc.deleted=0');
        $query->bindParam(':lang_id',$langId);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        if(!empty($data)){
            foreach ($data as $row){
                $result[$row->lang] = $this->system->urlWithoutLanguage($row->lang.'/'.$this->getPrefix('content',$row->lang).'/'.$row->link.'-'.$row->id);
            }
        }
        return $result;
    }

    public function getOrtherLanguagePage($langId):array
    {
        $query = $this->database::query('SELECT p.id,p.link,p.lang,p.lang_id FROM page p WHERE p.lang_id=:lang_id AND p.status=1 AND p.deleted=0');
        $query->bindParam(':lang_id',$langId);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        if(!empty($data)){
            foreach ($data as $row){
                $result[$row->lang] = $this->system->urlWithoutLanguage($row->lang.'/'.$row->link);
            }
        }
        return $result;
    }

    public function getPrefix(string $prefix,?string $lang="tr"): string
    {
        global $settings;
        if(isset($settings->{$prefix."_prefix_".$lang})){
            return $settings->{$prefix."_prefix_".$lang};
        }
        return false;
    }

    /**
     * @return array|false
     */
    public function getMailSendAdminList(){
        $getAdmins = $this->database::query('SELECT id,name,surname,email FROM users WHERE `rank`>=60 AND send_mail=1 AND status=1 AND deleted=0');
        $getAdmins->execute();
        $getAdminsList = $getAdmins->fetchAll(PDO::FETCH_OBJ);
        return $getAdminsList;
    }

    public function getAllSectors($lang,&$output = null, $parent_id = 0, $indent = null){
        global $db;
        $statement = $db::query("SELECT id, name FROM menu WHERE top_id =:parent_id AND lang=:lang ORDER BY top_id, show_order");

        $statement->execute(array(
            'parent_id'     => $parent_id,
            'lang'     => $lang
        ));

        // show the sectors one by one
        while($row = $statement->fetch()){
            if(empty($row['name'])){
                continue;
            }
            $output .= '<option value=' . $row['id'] . '>' . $indent . $row['name'] . "</option>";
            if($row['id'] != $parent_id){
                // In case the current sectors's id is different than $parent_id
                // I call the function again with new parameters(so it is recursive)
                $this->getAllSectors($lang,$output, $row['id'], $indent . " » ");
            }
        }
        // return the list of sectors(call out is in index.php)
        return $output;
    }

    public function getMenus($lang,&$output = null, $parent_id = 0, $indent = null){
        global $db,$session,$constants,$system;
        $statement = $db::query("SELECT id, name FROM menu WHERE top_id =:parent_id AND lang=:lang AND deleted=0 ORDER BY show_order ASC");

        $statement->execute(array(
            'parent_id'     => $parent_id,
            'lang'     => $lang
        ));

        // show the sectors one by one
        while($row = $statement->fetch()){
            if(empty($row['name'])){
                continue;
            }
            $output .= '<div class="row mb-2">
                <div class="col-md-9 d-flex align-items-center">
                ' . $indent . $row['name'] .'
                </div>               
                 ';
            $output .= '';
            $output .= '<div class="col-md-3">';
            if ($session->sessionRoleControl('menu', $constants::editPermissionKey) === true){
                $output .= '
                        <a href="'.$system->adminUrl("menu-settings?id=" . $row["id"]).'" class="btn btn-outline-success m-1">
                            <i class="fas fa-pencil-alt px-1"></i>
                            Düzenle
                        </a>';
            }
            if ($session->sessionRoleControl('menu', $constants::deletePermissionKey) === true){
                $output .= '
                        <button type="button" class="btn btn-outline-danger m-1 post_delete" data-delete-url="'.$system->adminUrl("menu?delete=" . $row["id"]).'">
                            <i class="fas fa-trash px-1"></i> Sil
                        </button>';
            }
            $output .= '</div>';
            $output .= '</div>';
            if($row['id'] != $parent_id){
                // In case the current sectors's id is different than $parent_id
                // I call the function again with new parameters(so it is recursive)
                $this->getMenus($lang,$output, $row['id'], $indent . " » ");
            }
        }
        // return the list of sectors(call out is in index.php)
        return $output;
    }
}