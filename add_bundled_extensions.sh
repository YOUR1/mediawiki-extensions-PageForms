#!/bin/sh
bundledextensions="WikiEditor TitleBlacklist SyntaxHighlight_GeSHi SpamBlacklist Renameuser Poem PdfHandler ParserFunctions Nuke LocalisationUpdate Interwiki InputBox ImageMap Gadgets ConfirmEdit CiteThisPage Cite"
bundledskins="Vector MonoBook Modern CologneBlue"

## Add extension function
add_extension() {
	echo "Adding extension $2 to branch $1"
	braid add "https://gerrit.wikimedia.org/r/p/mediawiki/extensions/$2.git" core/$1/extensions/$2 --branch=$1
}

add_skin() {
	echo "Added skin $2 to branch $1"
	braid add "https://gerrit.wikimedia.org/r/p/mediawiki/skins/$2.git" core/$1/skins/$2 --branch=$1
}

## Info message
echo "In what branch should the bundled extensions and skins be added?"
echo " - Available branches:"
for dir in $(ls core/)
do
    echo " -  $dir"
done
echo ""
read -p "Desired branch: " b

## Verify branch and directory
if [ ! -d "core/$b" ]; then
	echo "Invalid branch given!"
	exit 1
fi

## Loop over the bundled extensions to add them
for ext in $bundledextensions
do
	add_extension $b $ext
done

## Loop over the bundled skins to add them
for skin in $bundledskins
do
	add_skin $b $skin
done

## Echo success message
echo "Added bundled extensions and skins to branch: $b"
