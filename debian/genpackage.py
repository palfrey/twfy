from debian_bundle.changelog import Changelog
from os.path import getmtime
from time import strftime, localtime

def striphash(string):
	if string.find("#")!=-1:
		return string[:string.find("#")]
	else:
		return string

def pkglist(fname):
	return [striphash(x.strip()) for x in open(fname).readlines()]

depends = pkglist("conf/packages")
recommends = pkglist("conf/packages-recommended")

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
