generateMessagesForLocale(){
    locale="$1"

    local inputfile="i18n/locale/${locale}/LC_MESSAGES/codev.po"
    local outputfile="i18n/locale/${locale}/LC_MESSAGES/messages-${locale}.js"

    # generation du contenu du fichier
    fileContent=$(grep -E "^(msgid|msgstr)" "${inputfile}" |
        # on change les retour chariots pour mieux travailler avec sed (sed est single-line)
        tr '\n' '\r' |

        # on retabli un seul "msgid" par ligne, et on supprime les \r devant "\rmsgstr"
        sed 's/\rmsgid/\nmsgid/g;s/\rmsgstr/ msgstr/g' |

        # a ce stade, chaque ligne contient le texte et sa traduction, on peut convertir ça en JS
        # format des chaines (entree): 'msgid "texte original" msgstr "texte traduit"'
        # format des chaines (sortie): '"texte original": "texte traduit",'
        sed -E 's/^msgid (".*?") msgstr (".*?")/\1: \2,/g' |

        # on rétabli les retour chariots qui resteraient
        tr '\r' '\n')

    # TODO: utiliser un fichier template "window.MyLocaleLib.addLocale('LOCALE_NAME', {\nLOCALE_MESSAGES\n});

    # WIP
    # debut du fichier
    # echo "window.MyLocaleLib.addLocale('${locale}', {" > ${outputfile}
    # # contenu du fichier
    # echo "$fileContent ">> ${outputfile}
    # # fin du fichier
    # echo "});" >> ${outputfile}

    echo "
    window.MyLocaleLib.addLocale('${locale}', {
        ${fileContent}
    });" > ${outputfile}
}

# TODO: lister dynamiquement les locales
generateMessagesForLocale "de_DE"
generateMessagesForLocale "es_ES"
generateMessagesForLocale "fr"
generateMessagesForLocale "it_IT"
generateMessagesForLocale "pt_BR"
