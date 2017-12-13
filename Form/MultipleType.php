<?php

namespace VirtualAssembly\SemanticFormsBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class MultipleType extends UriType
{
		public function getParent()
		{
				return TextType::class;
		}
}
