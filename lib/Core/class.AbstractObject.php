<?php
/**
 * Created by JetBrains PhpStorm.
 * User: potherca
 * Date: 3/1/11
 * Time: 8:21 PM
 * To change this template use File | Settings | File Templates.
 */
namespace DarkHelmet\Core
{
    abstract class AbstractObject {
        public function getShortName(){
			return substr(get_called_class(), strrpos(get_called_class(), '\\')+1);
        }

        public function getNamespaceName(){
			return substr(get_called_class(), 0, strpos(get_called_class(), '\\'));
        }

        public function getNamespaceRoot(){
			return substr(get_called_class(), 0, strpos(get_called_class(), '\\'));
        }

        public function getNamespaceBase(){
	        return substr($this->getNamespacePath(), strrpos($this->getNamespacePath(), '\\')+1);
        }

        public function getNamespacePath(){
			return substr(get_called_class(), strlen($this->getNamespaceRoot())+1, -strlen($this->getShortName())-1);
        }
    }
}

#EOF