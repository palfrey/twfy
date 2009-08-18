from debian_bundle.changelog import Changelog
from os.path import getmtime
from time import strftime, localtime

depends = [x.strip() for x in open("conf/packages").readlines()]
depends.append("perl (>=5.8.1)")
depends.append("apache (>=1.3.28) | apache2 (>=2.2.12)") # FIXME: Should be 2.2.13, but not currently (2009-08-17) available in Debian
depends.append("libapache2-mod-php5")
depends.append("mysql-server (>=4.1)")

recommends = ["php5-xapian"]
depends.remove("php5-xapian")

subst = open("debian/mysociety-twfy-dependencies.substvars","w")
subst.write("mysociety-twfy-depends=%s\n"%(", ".join(depends)))
subst.write("mysociety-twfy-recommends=%s\n"%(", ".join(recommends)))
subst.close()

datefiles = ("conf/packages","INSTALL.txt", "debian/genpackage.py")
oldest = -1

for f in datefiles:
	if getmtime(f)>oldest:
		oldest = getmtime(f)

changes = Changelog(open("debian/changelog.orig").read())
changes._blocks[0].version = "0.cvs-%d"%int(oldest)
changes._blocks[0].date = strftime("%a, %d %b %Y %H:%M:%S +0000", localtime(oldest))
changes.write_to_open_file(open("debian/changelog","w"))
