# Sample lightweight Magento repository structure

## Instllation steps
1. Create a folder

    `mkdir sampleapp`\
    `cd sampleapp`
        
1. Clone this repository to a folder
   
    `git clone https://github.com/magento-architects/magento-project`
    
1. Create `source` folder within your root folder
    
    `mkdir source`\
    `cd source`

1. Clone magento2 repository to `../source` folder

    `git clone git@github.com:magento-architects/magento2/tree/split-framework`
    
1. Return back `magento-project` folder and run `composer install`

Framework components will be symlinked from `source` folder
