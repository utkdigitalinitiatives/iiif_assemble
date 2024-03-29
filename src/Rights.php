<?php

namespace Src;

class Rights {

    private $uri;
    public $data;

    public function __construct($uri)
    {

        $this->uri = $uri;
        $this->data = $this->getRightsParts();

    }

    private function dereferenceCCRequirements($requirements) {
        $results = [];
        $cc_requirements = (object)[
            "http://creativecommons.org/ns#Notice" => "copyright and license notices be kept intact",
            "http://creativecommons.org/ns#Attribution" => "credit be given to copyright holder and/or author",
            "http://creativecommons.org/ns#ShareAlike" => "derivative works be licensed under the same terms or compatible terms as the original work",
            "http://creativecommons.org/ns#SourceCode" => "source code (the preferred form for making modifications) must be provided when exercising some rights granted by the license",
            "http://creativecommons.org/ns#Copyleft" => "derivative and combined works must be licensed under specified terms, similar to those on the original work",
            "http://creativecommons.org/ns#LesserCopyleft" => "derivative works must be licensed under specified terms, with at least the same conditions as the original work; combinations with the work may be licensed under different terms"
        ];
        foreach ($requirements as $value) {
            $results[] = $cc_requirements->$value;
        }
        return $results;
    }

    private function dereferenceCCPermissions($permissions) {
        $results = [];
        $cc_permissions = (object)[
            "http://creativecommons.org/ns#Reproduction" => "making multiple copies",
            "http://creativecommons.org/ns#Distribution" => "distribution, public display, and publicly performance",
            "http://creativecommons.org/ns#DerivativeWorks" => "distribution of derivative works",
            "http://creativecommons.org/ns#Sharing" => "permits commercial derivatives, but only non-commercial distribution",
        ];
        foreach ($permissions as $value) {
            $results[] = $cc_permissions->$value;
        }
        return $results;
    }

    private function getRightsParts() {

        $rights_values = (object)[
            "http://rightsstatements.org/vocab/InC/1.0/" => (object)[
                "label" => "In Copyright",
                "badge" => "https://rightsstatements.org/files/buttons/InC.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the Item labeled with this Rights Statement is in copyright.",
                "definition" => "This Item is protected by copyright and/or related rights. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use. For other uses you need to obtain permission from the rights-holder(s)."
            ],
            "http://rightsstatements.org/vocab/InC-OW-EU/1.0/" => (object)[
                "label" => "In Copyright - EU Orphan Work",
                "badge" => "https://rightsstatements.org/files/buttons/InC-OW-EU.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the Item labeled with this Rights Statement has been identified as an ‘Orphan Work’ under the terms of the EU Orphan Works Directive.",
                "definition" => "This Item has been identified as an orphan work in the country of first publication and in line with Directive 2012/28/EU of the European Parliament and of the Council of 25 October 2012 on certain permitted uses of orphan works. For this Item, either (a) no rights-holder(s) have been identified or (b) one or more rights-holder(s) have been identified but none have been located even though a diligent search for the rights-holder(s) has been conducted. The results of the diligent search are available in the EU Orphan Works Database. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use.",
            ],
            "http://rightsstatements.org/vocab/InC-EDU/1.0/" => (object)[
                "label" => "In Copyright - Educational Use Permitted",
                "badge" => "https://rightsstatements.org/files/buttons/InC-EDU.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the Item labeled with this Rights Statement is in copyright but that educational use is allowed without the need to obtain additional permission.",
                "definition" => "This Item is protected by copyright and/or related rights. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use. In addition, no permission is required from the rights-holder(s) for educational uses. For other uses, you need to obtain permission from the rights-holder(s).",
            ],
            "http://rightsstatements.org/vocab/InC-NC/1.0/" => (object)[
                "label" => "In Copyright - Non-Commercial Use Permitted",
                "badge" => "https://rightsstatements.org/files/buttons/InC-NC.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the Item labeled with this Rights Statement is in copyright but that non-commercial use is allowed without the need to obtain additional permission.",
                "definition" => "This Item is protected by copyright and/or related rights. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use. In addition, no permission is required from the rights-holder(s) for non-commercial uses. For other uses you need to obtain permission from the rights-holder(s).",
            ],
            "http://rightsstatements.org/vocab/InC-RUU/1.0/" => (object)[
                "label" => "In Copyright - Rights-holder(s) Unlocatable or Unidentifiable",
                "badge" => "https://rightsstatements.org/files/buttons/InC-RUU.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the Item labeled with this Rights Statement has been identified as in copyright, but its rights-holder(s) either cannot be identified or cannot be located.",
                "definition" => "This Item is protected by copyright and/or related rights. However, for this Item, either (a) no rights-holder(s) have been identified or (b) one or more rights-holder(s) have been identified but none have been located. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use.",
            ],
            "http://rightsstatements.org/vocab/NoC-CR/1.0/" => (object)[
                "label" => "No Copyright - Contractual Restrictions",
                "badge" => "https://rightsstatements.org/files/buttons/NoC-CR.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the underlying Work is in the Public Domain, but the organization that has published the Item is contractually required to restrict certain forms of use by third parties.",
                "definition" => "Use of this Item is not restricted by copyright and/or related rights. As part of the acquisition or digitization of this Work, the organization that has made the Item available is contractually required to limit the use of this Item. Limitations may include, but are not limited to, privacy issues, cultural protections, digitization agreements or donor agreements. Please refer to the organization that has made the Item available for more information.",
            ],
            "http://rightsstatements.org/vocab/NoC-NC/1.0/" => (object)[
                "label" => "No Copyright - Non-Commercial Use Only",
                "badge" => "https://rightsstatements.org/files/buttons/NoC-NC.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the underlying Work is in the Public Domain, but the organization that has published the Item is contractually required to allow only non-commercial use by third parties.",
                "definition" => "This Work has been digitized in a public-private partnership. As part of this partnership, the partners have agreed to limit commercial uses of this digital representation of the Work by third parties. You can, without permission, copy, modify, distribute, display, or perform the Item, for non-commercial uses. For any other permissible uses, please review the terms and conditions of the organization that has made the Item available.",
            ],
            "http://rightsstatements.org/vocab/NoC-OKLR/1.0/" => (object)[
                "label" => "No Copyright - Other Known Legal Restrictions",
                "badge" => "https://rightsstatements.org/files/buttons/NoC-OKLR.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the underlying Work is in the Public Domain, but that there are known restrictions imposed by laws other than copyright and/or related rights on the use of the Item by third parties.",
                "definition" => "Use of this Item is not restricted by copyright and/or related rights. In one or more jurisdictions, laws other than copyright are known to impose restrictions on the use of this Item. Please refer to the organization that has made the Item available for more information.",
            ],
            "http://rightsstatements.org/vocab/NoC-US/1.0/" => (object)[
                "label" => "No Copyright - United States",
                "badge" => "https://rightsstatements.org/files/buttons/NoC-US.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the Item is in the Public Domain under the laws of the United States, but that a determination was not made as to its copyright status under the copyright laws of other countries.",
                "definition" => "The organization that has made the Item available believes that the Item is in the Public Domain under the laws of the United States, but a determination was not made as to its copyright status under the copyright laws of other countries. The Item may not be in the Public Domain under the laws of other countries. Please refer to the organization that has made the Item available for more information.",
            ],
            "http://rightsstatements.org/vocab/CNE/1.0/" => (object)[
                "label" => "Copyright Not Evaluated",
                "badge" => "https://rightsstatements.org/files/buttons/CNE.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the organization that has published the Item has not evaluated the copyright and related rights status of the Item.",
                "definition" => "The copyright and related rights status of this Item has not been evaluated. Please refer to the organization that has made the Item available for more information. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use.",
            ],
            "http://rightsstatements.org/vocab/UND/1.0/" => (object)[
                "label" => "Copyright Undetermined",
                "badge" => "https://rightsstatements.org/files/buttons/UND.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the organization that has made the Item available has reviewed the copyright and related rights status of the Item, but was unable to determine the copyright status of the Item.",
                "definition" => "The copyright and related rights status of this Item has been reviewed by the organization that has made the Item available, but the organization was unable to make a conclusive determination as to the copyright status of the Item. Please refer to the organization that has made the Item available for more information. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use.",
            ],
            "http://rightsstatements.org/vocab/NKC/1.0/" => (object)[
                "label" => "No Known Copyright",
                "badge" => "https://rightsstatements.org/files/buttons/NKC.dark-white-interior-blue-type.svg",
                "description" => "This Rights Statement indicates that the organization that has published the Item believes that no copyright or related rights are known to exist for the Item, but that a conclusive determination could not be made.",
                "definition" => "The organization that has made the Item available reasonably believes that the Item is not restricted by copyright or related rights, but a conclusive determination could not be made. Please refer to the organization that has made the Item available for more information. You are free to use this Item in any way that is permitted by the copyright and related rights legislation that applies to your use.",
            ],
        ];
        $uri = $this->uri;
        if( isset($rights_values->$uri) ) {
            return $rights_values->$uri;
        }
        elseif(gettype($this->uri) == Null) {
            $cne_uri = "http://rightsstatements.org/vocab/CNE/1.0/";
            return $rights_values->$cne_uri;
        }
        elseif (strpos($uri, 'creativecommons')){
            $creative_commons_uri = str_replace("rdf", "", $uri);
            $creative_commons_uri = str_replace("https", "http", $creative_commons_uri);
            $xml = Request::getCreativeCommons($creative_commons_uri);
            $document = new XPath($xml['body']);
            $document2 = new SimpleXPath($xml['body']);
            $badge = $document->query('//img/@src' )[0];
            $requires = $document2->getCreativeCommonsDetails("//cc:License/cc:requires/@rdf:resource");
            $requires = $this->dereferenceCCRequirements($requires);
            $permits = $document2->getCreativeCommonsDetails("//cc:License/cc:permits/@rdf:resource");
            $permits = $this->dereferenceCCPermissions($permits);
            return (object)[
                "label" => $document->query('license-name')[0],
                "badge" => $badge,
                "uri" => $creative_commons_uri,
                "requires" => $requires,
                "permits" => $permits
            ];
        }
        else {
            $cne_uri = "http://rightsstatements.org/vocab/CNE/1.0/";
            return $rights_values->$cne_uri;
        }

    }

}

?>