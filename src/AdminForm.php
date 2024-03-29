<?php

namespace OS\MimozaCore;
use Includes\Project\Constants;

class AdminForm
{

    /**
     * An instance of the Functions class
     *
     * @var Functions
     */
    public Functions $functions;

    /**
     * Language code
     *
     * @var string
     */
    public string $lang;

    /**
     * If it's not present, all inputs are prefixed with lang codes
     *
     * @var int
     */
    public int $formNameWithoutLangCode;

    /**
     *
     */
    public function __construct()
    {
        $this->functions = new Functions();
    }

    /**
     * It's return HTML input according to giving options
     *
     * @param string $name
     * @param array $item
     * @param array|object|null $data
     * @return string
     */
    public function input(string $name, array $item = [], $data = null): string
    {
        if (is_array($data)) {
            $data = (object)$data;
        }
        $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $name . "_" . $this->lang : $name;
        $type = $item["type"] ?? "text";
        $item_hidden = $item["item_hidden"] ?? null;
        $label = $item["label"] ?? null;
        $input_group = isset($item["input_group"]) ? 1 : 0;
        $id = "id-" . $this->functions->permalink($name_lang);
        $group_icon = $item["group_icon"] ?? null;
        $required = isset($item["required"]) && (int)$item["required"] === 1 ? "required validate[required]" : null;
        $disabled = isset($item["disabled"]) && (int)$item["disabled"] === 1 ? "disabled" : null;
        $class = $item["class"] ?? null;
        if (!empty($this->lang)) {
            $value = !empty($data) && isset($data->{$this->lang}[$name]) ? $data->{$this->lang}[$name] : null;
        } else {
            $value = !empty($data) && isset($data->{$name}) ? $data->{$name} : null;
        }


        if (isset($item["order"]) && empty($value)) {
            $value = 1;
        }
        $max_size = 5000;
        if (isset($item["max_size"])) {
            $max_size = (int)$item["max_size"];
        }

        $html = '<div class="' . ($input_group == 1 ? "input-group" : "form-group") . ' mb-1" id="div_' . $name . '" ' . ($item_hidden == 1 ? $item["show_data"] == $item["show_value"] ? null : "style='display:none;'" : null) . '>';
        $html .= '<div class="d-block w-100"><label for="' . $id . '">' . $label . '</label></div>';
        $html .= '<input type="' . $type . '" class="form-control ' . $class . ' ' . $required . '" name="' . $name_lang . '" id="' . $id . '" placeholder="' . $label . '" value="' . $value . '" ' . $disabled . '>';
        if ($input_group == 1) {
            $html .= '<div class="input-group-append">
                                    <div class="input-group-text">
                                        <span class="' . $group_icon . '"></span>
                                    </div>
                                </div>';
        }
        if (isset($item["description"])) {
            $html .= '<span class="form-text text-muted">' . $item["description"] . '</span>';
        }
        $html .= '</div>';
        if (isset($item["order"])) {
            $html .= '
                            <script>
                                $(document).ready(function(){
                                   $("#' . $id . '").TouchSpin({
                                    verticalbuttons: true,
                                    min: 1,
                                    max: ' . $max_size . ',
                                    stepinterval: 0,
                                    verticalupclass: "glyphicon glyphicon-plus",
                                    verticaldownclass: "glyphicon glyphicon-minus"
                                    });
                                });
                            </script>
                        ';
        }

        return $html;
    }

    public function order(string $name, array $item = [], array $data = null): string
    {
        $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $name . "_" . $this->lang : $name;
        $id = "id-" . $this->functions->permalink($name_lang);
        if (!empty($this->lang)) {
            $value = !empty($data) && isset($data[$this->lang][$name]) ? $data[$this->lang][$name] : 1;
        } else {
            $value = !empty($data) && isset($data[$name]) ? $data[$name] : 1;
        }
        $label = $item["label"] ?? null;
        $html = null;
        $html .= '<div class="col-12 mb-1">
                <div class="col-12"><label for="">' . $label . '</label></div>
                    <div class="input-group w-100 p-0"> 
                        <input type="number" class="touchspin form-control" id="' . $id . '" name="' . $name_lang . '" value="' . $value . '" />
                    </div> 
                </div>';
        $html .= '
            <script>
                $(document).ready(function(){
                   $("#' . $id . '").TouchSpin({ 
                    min: 1, 
                    buttondown_class: "btn btn-primary",
                    buttonup_class: "btn btn-primary",
                    buttondown_txt: feather.icons["minus"].toSvg(),
                    buttonup_txt: feather.icons["plus"].toSvg()
                    });
                });
            </script>
        ';
        return $html;
    }

    /**
     * @param $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function select(string $name, array $item = [], $data = null): string
    {
        if (is_array($data)){
            $data = (object)$data;
        }
        $brackets = isset($item['multiple']) ? '[]' : '';
        $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $name . "_" . $this->lang . $brackets : $name;
        $label = isset($item["label"]) ? $item["label"] : null;
        $multiple = isset($item["multiple"]) ? 'multiple="multiple"' : null;
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $value = null;
        if(!empty($data) && empty($this->formNameWithoutLangCode) && isset($data->{$this->lang}[$name])){
            $value = $data->{$this->lang}[$name] ?? '-1';
        }elseif (!empty($data)){
            $value = $data->{$name} ?? '-1';
        }
        $html = '<div class="form-group">
                       <label for="id_' . $name . '">' . $label . '</label>
                       <select class="form-control select2bs4 ' . $required . '" ' . $multiple . ' name="' . $name_lang . '"  id="id_' . $name_lang . '" style="width: 100%;">
                           <option value="">Seçiniz</option>';
        $each_item = null;
        if (isset($item["multiple_lang_select"])) {
            if (isset($item["select_item"][$this->lang])) {
                foreach ($item["select_item"][$this->lang] as $item_key => $item_value) {
                    $each_item .= '<option value="' . $item_key . '" ' . ($value == $item_key ? "selected" : null) . '>' . $item_value . '</option>';
                }
            }
        } else {
            foreach ($item["select_item"] as $item_key => $item_value) {
                $each_item .= '<option value="' . $item_key . '" ' . ($value == $item_key ? "selected" : null) . '>' . $item_value . '</option>';
            }
        }

        $html .= $each_item . '</select>
           </div>';
        $html .= '
               <script>
                   $(document).ready(function(){
                       //Initialize Select2 Elements
                       $(".select2bs4").select2({
                           theme: "bootstrap4"
                       });
                   });
               </script>
           ';
        return $html;
    }

    /**
     * @param $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function button(string $name, array $item = [], $data = null): string
    {
        //$name = $name."_".$this->lang;
        $text = isset($item["text"]) ? $item["text"] : null;
        $type = isset($item["type"]) ? $item["type"] : "submit";
        $btn_type = isset($item["btn_class"]) ? $item["btn_class"] : "btn btn-primary";
        $form_name = isset($item["form_name"]) ? $item["form_name"] : "pageForm";
        $onclick_function = isset($item["onclick_function"]) ? $item["onclick_function"] : null;
        $class = isset($item["class"]) ? $item["class"] : null;
        $icon_type = isset($item["icon"]) ? "<i class='" . $item["icon"] . "'></i>" : null;
        $html = '<button type="' . $type . '" ' . (!empty($onclick_function) ? 'onclick="' . $onclick_function . '"' : null) . ' form="' . $form_name . '" id="id_' . $name . '" value="1" name="' . $name . '" class="' . $btn_type . ' ' . $class . '">' . $icon_type . ' ' . $text . '</button>';
        return $html;
    }

    /**
     * @param string $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function file(string $name, array $item = [], $data = null): string
    {
        if (is_array($data)){
            $data = (object)$data;
        }
        $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $name . "_" . $this->lang : $name;
        $label = $item["label"] ?? null;
        $required = isset($item["required"]) && (int)$item["required"] === 1 ? "required validate[required]" : null;
        $file_key = $item["file_key"] ?? null;
        $show_image_label_text = $item["show_image_label_text"] ?? "Mevcut Dosya ->";
        $delete_link = $item["delete_link"] ?? null;
        $html = '<div class="form-group">
                        <label for="id_' . $name_lang . '">' . $label . '</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" name="' . $name_lang . '" class="custom-file-input ' . $required . '" id="id_' . $name_lang . '">
                                <label class="custom-file-label" for="id_img">Seç</label>
                            </div> 
                        </div>';

        if(array_key_exists($file_key, Constants::fileTypePath) && empty($this->formNameWithoutLangCode) &&  isset($data->{$this->lang}[$name]) && !empty($data->{$this->lang}[$name]) && file_exists(Constants::fileTypePath[$file_key]["full_path"] . $data->{$this->lang}[$name])){
            $imgUrl = Constants::fileTypePath[$file_key]["url"] . $data->{$this->lang}[$name];
        }else if (array_key_exists($file_key, Constants::fileTypePath) && isset($data->{$name}) && !empty($data->{$name}) && file_exists(Constants::fileTypePath[$file_key]["full_path"] . $data->{$name})) {
            $imgUrl = Constants::fileTypePath[$file_key]["url"] . $data->{$name};
        }
        if(isset($imgUrl)){
            $html .= '<p class="mt-1">';
            $html .= '<a href="' . $imgUrl . '" data-toggle="lightbox"> <img src="'.$imgUrl.'" style="width:50px;">';
            $html .= '</a>';
            if (!empty($delete_link)) {
                $html .= '<a href="' . $delete_link . '" class="btn btn-danger ml-2">Dosyayı SİL <i class="fa fa-trash"></i></a>';
            }
            $html .= '</p>';
        }
        $html .= '</div>';
        $html .= "<script>
                    $(\".custom-file-input\").on(\"change\", function(e) {
                        if (e.target.files.length) {
                            $(this).next('.custom-file-label').html(e.target.files[0].name);
                        }
                    });
                    </script>";
        return $html;
    }

    /**
     * @param string $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function textarea(string $name, array $item = [], $data = null): string
    {
        if (is_array($data)){
            $data = (object)$data;
        }
        $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $name . "_" . $this->lang : $name;
        $item_hidden = isset($item["item_hidden"]) ? $item["item_hidden"] : null;
        $label = isset($item["label"]) ? $item["label"] : null;
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $disabled = isset($item["disabled"]) && $item["disabled"] == 1 ? "disabled" : null;
        $class = isset($item["class"]) ? $item["class"] : null;
        $value = null;
        if (!empty($this->lang)) {
            $value = !empty($data) && isset($data->{$this->lang}[$name]) ? $data->{$this->lang}[$name] : null;
        } else {
            $value = !empty($data) && isset($data->{$name}) ? $data->{$name} : null;
        }
        $html = '<div class="form-group" id="div_' . $name_lang . '" ' . ($item_hidden == 1 ? $item["show_data"] == $item["show_value"] ? null : "style='display:none;'" : null) . '>
                        <label for="id_' . $name_lang . '">' . $label . '</label>
                        <textarea class="form-control ' . $class . ' ' . $required . '" id="id_' . $name_lang . '" name="' . $name_lang . '" rows="5" ' . $disabled . '>' . $value . '</textarea>
                    </div>';
        return $html;
    }

    /**
     * @param $name
     * @param array $item
     * @param null $data
     * @return string
     * @throws \Exception
     */
    public function dateMask(string $name, array $item = [], $data = null): string
    {
        $label = $item["label"] ?? null;
        $value = !empty($data) && isset($data[$name]) ? $this->functions->date_long($data[$name]) : null;
        $value2 = !empty($data) && isset($data[$name]) ? $data[$name] : null;
        return '<div class="form-group">
                  <label>' . $label . '</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                    </div>
                    <input type="text" name="' . $name . '" class="form-control" data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask>
                  </div>
                  <!-- /.input group -->
                </div>
                <script>
                    $(document).ready(function(){
                       //Datemask dd/mm/yyyy
                        $("#id_' . $name . '").inputmask("dd/mm/yyyy", { "placeholder": "dd/mm/yyyy" });
                        $("[data-mask]").inputmask();
                    });
                </script>
                ';
    }

    /**
     * @param $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function dateRange(string $name, array $item = [], $data = null): string
    {
        $label = $item["label"] ?? null;
        $value = !empty($data) && isset($data[$name]) ? $data[$name] : null;
        return '<div class="form-group">
                        <label>' . $label . '</label>                
                        <div class="input-group">
                            <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="far fa-calendar-alt"></i>
                            </span>
                            </div>
                            <input type="text" class="form-control float-right" value="' . $value . '" name="' . $name . '" id="id_' . $name . '">
                        </div>
                        <!-- /.input group -->
                    </div>
                <script>
                    $(document).ready(function(){
                       $("#id_' . $name . '").daterangepicker({
                            locale: {
                              format: "DD-MM-YYYY"
                            }
                       });
                    });
                </script>
                ';
    }

    /**
     * @param string $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function inputTags(string $name, array $item = [], $data = null): string
    {
        if (is_array($data)) {
            $data = (object)$data;
        }

        $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $name . "_" . $this->lang : $name;
        $type = $item["type"] ?? "text";
        $item_hidden = $item["item_hidden"] ?? null;
        $label = $item["label"] ?? null;
        $input_group = isset($item["input_group"]) ? 1 : 0;
        $id = "id-" . $this->functions->permalink($name_lang);
        $group_icon = $item["group_icon"] ?? null;
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $class = $item["class"] ?? null;
        $value = null;
        if(!empty($data) && empty($this->formNameWithoutLangCode) && isset($data->{$this->lang}->{$name})){
            $value = $data->{$this->lang}->{$name} ?? null;
        }elseif (!empty($data)){
            $value = $data->{$name} ?? null;
        }

        if (isset($item["order"]) && empty($value)) {
            $value = 1;
        }
        $html = '<div class="' . ($input_group == 1 ? "input-group" : "form-group") . ' mb-3" id="div_' . $name . '" ' . ($item_hidden == 1 ? $item["show_data"] == $item["show_value"] ? null : "style='display:none;'" : null) . '>';
        $html .= '<div class="d-block w-100"><label for="' . $id . '">' . $label . '</label></div>';
        $html .= '<div class="bootstrap-tagsinput w-100">
                    <input type="' . $type . '" class="form-control ' . $class . ' ' . $required . '" name="' . $name_lang . '" id="' . $id . '" placeholder="Ekle" value="' . $value . '" data-role="tagsinput" placeholder="Ekle">
                </div>';
        if ($input_group == 1) {
            $html .= '<div class="input-group-append">
                                    <div class="input-group-text">
                                        <span class="' . $group_icon . '"></span>
                                    </div>
                                </div>';
        }
        $html .= '</div>';
        $html .= '
                <script>
                    $(document).ready(function(){
                       $(".tags-input").tagsinput();
                    });
                </script>
            ';

        return $html;
    }

    /**
     * @param array $items
     * @param null $data
     * @return string
     */
    public function checkbox(array $items = [], $data = null): string
    {
        $checkbox = "";
        foreach ($items["option"] as $item) {
            $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $item["name"] . "_" . $this->lang : $item["name"];
            $id = "id_" . $name_lang;
            $check_value = $item["value"];
            $check_value = $item["value"];
            if (!empty($this->lang) && empty($this->formNameWithoutLangCode)) {
                $value = !empty($data) && isset($data[$this->lang][$item["name"]]) ? $data[$this->lang][$item["name"]] : null;
            } else {
                $value = !empty($data) && isset($data[$name_lang]) ? $data[$name_lang] : null;
            }
            $checkbox .= '<div class="icheck-primary d-inline">
                        <input type="checkbox" class="form-check-input" name="' . $name_lang . '" id="' . $id . '" value="' . $check_value . '" ' . ((int)$value === (int)$check_value ? "checked" : null) . '>
                        <label for="' . $id . '" class="form-check-label user-select-none">
                          ' . $item["label"] . '
                        </label>
                      </div>';
        }
        return '<div class="form-group clearfix">' . $checkbox . '</div>';
    }

    /**
     * @param string $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function date(string $name, array $item = [], $data = null): string
    {
        $label = $item["label"] ?? null;
        $value = !empty($data) && isset($data[$this->lang][$name]) && !empty($data[$this->lang][$name]) ? $data[$this->lang][$name] : null;
        $name_lang = !empty($this->lang) && empty($this->formNameWithoutLangCode) ? $name . "_" . $this->lang : $name;
        $required = isset($item["required"]) && (int)$item["required"] === 1 ? "required validate[required]" : null;
        $id = "id_" . $name_lang;
        $html = '<div class="form-group">
                  <label>' . $label . '</label>
                    <div class="input-group date" id="' . $id . '" data-target-input="nearest">
                        <input type="text" class="form-control datetimepicker-input ' . $required . '" name="' . $name_lang . '" value="' . $value . '" data-target="#' . $id . '"/>
                        <div class="input-group-append" data-target="#' . $id . '" data-toggle="datetimepicker">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                </div>';
        $html .= '
                <script>
                    $(document).ready(function(){
                       //Date picker
                        $("#' . $id . '").datetimepicker({
                            format: "DD-MM-YYYY",
                            locale: "' . $_SESSION["lang"] . '",
                        });
                    });
                </script>
            ';
        return $html;
    }
}