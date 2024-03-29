<?php

namespace OS\MimozaCore;

use Includes\Project\Constants;
use Exception;

class Form
{

    /**
     * @var Functions
     */
    public Functions $functions;

    public function __construct()
    {
        $this->functions = new Functions();
    }

    /**
     * @param string $name
     * @param array $item
     * @param null $data
     * @return string
     */
    public function input(string $name, array $item = array(), $data = null): string
    {
        $item_hidden = $item["item_hidden"] ?? null;
        $label = $item["label"] ?? null;
        $label_description = $item["label_description"] ?? null;
        $class = $item["class"] ?? null;
        $addon = $item["addon"] ?? null;
        $type = $item["type"] ?? "text";
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $required_label = isset($item["required"]) && $item["required"] == 1 ? "required" : null;
        $value = !empty($data) && isset($data[$name]) ? $data[$name] : null;
        return '<div class="mb-3" id="div_' . $name . '" ' . ($item_hidden == 1 ? $item["show_data"] == $item["show_value"] ? null : "style='display:none;'" : null) . '>
                      <label for="id_' . $name . '" id="div_' . $name . '" class="form-label ' . $required_label . '">' . $label . $label_description . '</label>
                      <input type="' . $type . '" class="form-control ' . $class . ' ' . $required . '" name="' . $name . '" id="id_' . $name . '" placeholder="' . $label . '" value="' . $value . '" ' . $addon . '>
                </div>';
    }

    public function checkboxOrRadio(string $name, array $item = array(), $data = null): string
    {
        $nameFormat = array_key_exists("multiple",$item) && $item["multiple"] === true ? $name."[]":$name;
        $label = $item["label"] ?? null;
        $label_description = $item["label_description"] ?? null;
        $class = $item["class"] ?? null;
        $addon = $item["addon"] ?? null;
        $values = $item["items"] ?? [];
        $type = isset($item["type"]) && $item["type"] === "checkbox" ? "checkbox":"radio";
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $required_label = isset($item["required"]) && $item["required"] == 1 ? "required" : null;
        $value = !empty($data) && isset($data[$name]) ? $data[$name] : null;
        $html = '<div class="mb-3" id="div_' . $name . '">
                      <label for="id_' . $name . '" id="div_' . $name . '" class="form-label ' . $required_label . '">' . $label . $label_description . '</label>
                      <div class="col-12 p-0">';
        foreach ($values as $k=>$v){
            $id = "id_".$name."_".$k;
            $html .= '<div class="form-check form-check-inline">
                              <input class="form-check-input '.$required.'" name="'.$nameFormat.'" type="'.$type.'" id="'.$id.'" value="'.$k.'">
                              <label class="form-check-label" for="'.$id.'">'.$v.'</label>
                            </div>';
        }
        $html .='    </div>
                </div>';

        return $html;
    }

    /**
     * @param string $name
     * @param array $item
     * @param  $data
     * @return string
     */
    public function select(string $name, array $item = array(), $data = null): string
    {
        $label = $item["label"] ?? null;
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $required_label = isset($item["required"]) && $item["required"] == 1 ? "required" : null;
        $value = !empty($data) && isset($data[$name]) ? $data[$name] : null;
        $html = '<div class="form-group mb-3">
                        <label for="id_' . $name . '" class="' . $required_label . ' form-label">' . $label . '</label>
                        <select class="form-select select2bs4 ' . $required . '" name="' . $name . '"  id="id_' . $name . '" style="width: 100%;">
                            <option value="">Seçiniz</option>';
        $each_item = null;
        foreach ($item["select_item"] as $item_key => $item_value) {
            $each_item .= '<option value="' . $item_key . '" ' . ($value == $item_key ? "selected" : null) . '>' . $item_value . '</option>';
        }
        $html .= $each_item . '</select>
            </div>';
        return $html;
    }

    /**
     * @param string $name
     * @param array $item
     * @param null $data
     * @return string
     * @throws Exception
     */
    public function date(string $name, array $item = [], $data = null):string
    {

        $label = $item["label"] ?? null;
        $format = $item["format"] ?? "dd.mm.yyyy";
        $dateLang = $item["language"] ?? "tr";
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $value = !empty($data) && isset($data[$name]) ? $this->functions->dateLong($data[$name]) : null;
        $value2 = !empty($data) && isset($data[$name]) ? $data[$name] : null;
        $removeName = "'".$name."'";
        return '<div class="mb-3">
                    <label for="dtp_input_'.$name.'" class="form-label">' . $label . '</label>
                    <div class="d-flex form-date-custom">
                        <div class="input-group  date div_' . $name . '" data-date="" data-link-field="dtp_input_' . $name . '">
                            <input type="text" class="form-control '.$required.'" value="' . $value . '" readonly placeholder="" aria-label="" aria-describedby="id_'.$name.'">
                            <input type="hidden" id="dtp_input_' . $name . '" value="' . $value2 . '" name="' . $name . '" />
                            <button class="btn btn-outline-secondary btn-calendar" type="button" id="id_'.$name.'">
                                <i class="fa-solid fa-calendar-days"></i> 
                            </button> 
                        </div>
                        <button class="btn btn-outline-secondary" onclick="dateRemove(this)" type="button">
                                <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
                <script>
                    $(document).ready(function(){
                       let FromEndDate = new Date();
                       $(".div_' . $name . '").datepicker({
                            endDate: FromEndDate,
                            format: "'.$format.'" ,
                            language:  "'.$dateLang.'", 
                            autoclose: 1, 
                        });
                    });
                </script>
                ';
    }

    /**
     * @param string $name
     * @param array $item
     * @param $data
     * @return string
     */
    public function textarea(string $name, array $item = [], $data = null): string
    {
        $id = "id_" . $name;
        $label = $item["label"] ?? null;
        $class = $item["class"] ?? null;
        $required = isset($item["required"]) && (int)$item["required"] === 1 ? "required validate[required]" : null;
        $required_label = isset($item["required"]) && (int)$item["required"] === 1 ? "required" : null;
        $value = !empty($data) && isset($data[$name]) ? $data[$name] : null;
        return '<div class="mb-3">
          <label for="' . $id . '" class="form-label ' . $required_label . '">' . $label . '</label>
          <textarea class="form-control ' . $class . ' ' . $required . '" id="' . $id . '" name="' . $name . '" rows="5" placeholder="' . $label . '">' . $value . '</textarea>
        </div>';
    }

    /**
     * @param string $name
     * @param array $item
     * @return string
     */
    public function button(string $name, array $item = array()) : string
    {
        $text = $item["text"] ?? "Kaydet";
        $type = $item["type"] ?? "submit";
        $class = $item["class"] ?? null;
        $value = $item["value"] ?? 0;
        $btn_class = $item["btn_class"] ?? "btn btn-success";
        $id = "id_" . $name;
        return '<button type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . $value . '" class="' . $btn_class . ' ' . $class . '">' . $text . '</button>';
    }

    /**
     * @param string $name
     * @param array $item
     * @param $data
     * @return string
     */
    public function file(string $name, array $item = array(), $data = null): string
    {
        global $fileTypePath;
        $label = $item["label"] ?? null;
        $labelInfo = $item["label_info"] ?? null;
        $pathKey = $item["pathKey"] ?? "default";
        $onchange = isset($item["onchange"]) ? "onchange='".$item["onchange"]."'":null;
        $accept = isset($item["accept"]) ? "accept='".$item["accept"]."'":null;
        $required = isset($item["required"]) && $item["required"] == 1 ? "required validate[required]" : null;
        $html = '<div class="mb-3">
                      <label for="id_' . $name . '" class="form-label">' . $label . ' '.$labelInfo.'</label>
                      <input class="form-control '.$required.'" type="file" name="' . $name . '" id="id_' . $name . '" '.$onchange.' '.$accept.'>
                    </div>';

        if (isset($data[$name]) && !empty($data[$name]) && file_exists(Constants::fileTypePath[$pathKey]["full_path"] . $data[$name])) {

            $html .= '<p class="mt-1">Görsel';
            $html .= '<a href="' . Constants::fileTypePath[$pathKey]["url"] . $data[$name] . '" class="text-decoration-none" data-fancybox="gallery"> <img src="'.Constants::fileTypePath[$pathKey]["url"] . $data[$name].'" style="width:40px;" >';
            $html .= '</a>';
            $html .= '</p>';

        }
        return $html;
    }
}