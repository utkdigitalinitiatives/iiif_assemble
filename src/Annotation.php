<?php

namespace Src;

class Annotation
{
    public function __construct($canvas, $text, $page)
    {

        $this->canvas = $canvas;
        $this->value = $text;
        $this->page = $page;
        $this->body = self::build();

    }

    private function build() {
        return array(
            "id" => $this->canvas . "/annopage-" . $this->page,
            "type" => "AnnotationPage",
            "items" => array(
                "id" => $this->canvas . "/annopage-" . $this->page . "/annotation-" . $this->page,
                "type" => "Annotation",
                "motivation" => "commenting",
                "body" => (object) [
                    "type" => "TextualBody",
                    "language" => "en",
                    "format" => "text/plain",
                    "value" => $this->value
                ],
                "target" => $this->canvas,
            )
        );
    }
}