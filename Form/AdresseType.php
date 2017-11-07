<?php

namespace VirtualAssembly\SemanticFormsBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class AdresseType extends UriType
{
		public function getParent()
		{
				return TextType::class;
		}
}
