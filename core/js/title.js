var text = ["$$", "$ $", "$ S $", "$ SY $", "$ SYS $", "$ SYST $", "$ SYSTE $", "$ SYSTE $", "$ SYSTEM  $", "$ SYSTEMS o$", "$  SYSTEMS ov $", "$  SYSTEMS ovh $", "$  SYSTEMS ov $", "$  SYSTEMS o $", "$ SYSTEM  $", "$ SYSTEM $", "$ SYSTEM $", "$ SYSTE $", "$ SYST $", "$ SYS $", "$ SY $", "$ S $","$ $","$$"];
var counter = 0;
var inst = setInterval(change, 300);

function change() {
    document.title = text[counter];
    counter++;
    if (counter >= text.length) {
        counter = 0;
    }
}
