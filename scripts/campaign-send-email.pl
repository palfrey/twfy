#!/usr/bin/perl -I ../../perllib/ -w
use strict;
use lib "loader/";

my $mailshot_name = 'freeourbills_email_4.txt';
my $test_email = "";
my $type = "all";
my $dryrun = 1;

$test_email = 'francis@flourish.org';
$test_email = 'tom@mysociety.org';
$test_email = 'frabcus@fastmail.fm';

my $amount = 1000000;

use DBI;
use URI::Escape;
use Text::CSV;
use Data::Dumper;

use mySociety::Config;
use mySociety::Email;
use mySociety::EmailUtil;

use FindBin;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASS'), { RaiseError => 1, PrintError => 0 });

# Read in the dump file of EDM signature etc.
my $consvals = {};
my $csv = Text::CSV_XS->new ({ binary  => 1 });
open (CSV, "<", "../../../dumps/edm_status.csv") or die $!;
while (<CSV>) {
    if ($csv->parse($_)) {
        my ($pid,$name,$party,$constituency,$signed_2141,$modcom,$minister) = $csv->fields();
        $consvals->{$constituency}->{pid} = $pid;
        $consvals->{$constituency}->{name} = $name;
        $consvals->{$constituency}->{party} = $party;
        $consvals->{$constituency}->{constituency} = $constituency;
        $consvals->{$constituency}->{signed_2141} = $signed_2141;
        $consvals->{$constituency}->{modcom} = $modcom;
        $consvals->{$constituency}->{minister} = $minister;
    } else {
        my $err = $csv->error_input;
        print "Failed to parse line: $err";
    }
}
close CSV;
#        print Dumper($consvals);

# Extra where clause
my $where = "";
if ($test_email ne "") {
    $where = "and campaigners.email = '$test_email'";
}
my $already_clause = "
    left join campaigners_sent_email on 
        campaigners_sent_email.campaigner_id = campaigners.campaigner_id 
        and email_name = ?
    where email_name is null and confirmed";

# Create query string
my $query;
if ($type eq "all") {
    $query = "select campaigners.email, campaigners.token as token, 
        campaigners.campaigner_id as campaigner_id, 
        campaigners.postcode as postcode, campaigners.constituency as constituency
        from campaigners
        $already_clause $where group by campaigners.email";
} else {
    die "Choose type"
}
$query .= " limit $amount";

# Send mailshot
my $sth = $dbh->prepare($query);
$sth->execute($mailshot_name);
my $all = $sth->fetchall_hashref('email');
print "Sending to " . $sth->rows . " people\n";
foreach my $k (keys %$all)
{
    my $data = $all->{$k};

    my $email = $data->{'email'};
    my $campaigner_id = $data->{'campaigner_id'};
    my $token = $data->{'token'};
    my $constituency = $data->{'constituency'};
    $constituency =~ s/&amp;/&/;
    $constituency =~ s/&ocirc;/\xf4/;
    my $postcode = $data->{'postcode'};
    my $realname = undef;
    my $url_postcode = uri_escape($postcode);
    my $mp_name = $consvals->{$constituency}->{name};
    die "no mp name for $constituency" if !$mp_name;

    my $to = $email;

    print "Sending to $to...";
    
    my $template_name = $mailshot_name;
    my $template_file = "../www/includes/easyparliament/templates/emails/$template_name";
    open (TEXT, $template_file) or die "Can't open email template $template_file : $!";
    my $template = join('', <TEXT>);
    close TEXT;

    my $email_contents = mySociety::Email::construct_email({
            From => [ mySociety::Config::get('CONTACTEMAIL'), "Free Our Bills" ],
            To => [ $to ],
            _template_ => $template,
            _parameters_ => {
                token => $token,
                postcode => $postcode,
                url_postcode => $url_postcode,
                constituency => $constituency,
                mp_name => $mp_name
            }
        });

    # for just printing members
    #if ($consvals->{$constituency}->{modcom}) {
    #    print "modcom\t$email\t$mp_name\t$constituency\n";
    #}
    #next;

#    if (!$consvals->{$constituency}->{signed_2141} &&
#    !$consvals->{$constituency}->{modcom} &&
#    !$consvals->{$constituency}->{minister}) {
        print " MP $mp_name... ";
        if (!$dryrun) {
            my $ret = mySociety::EmailUtil::send_email($email_contents, mySociety::Config::get('CONTACTEMAIL'), $to);
            die "failed to send email: $ret\n" if $ret != 0;
        } else {
            print "dry run... ";
        }
#    } else {
#        print " NOT!!!";
#        print " signed_2141" if $consvals->{$constituency}->{signed_2141};
#        print " modcom" if $consvals->{$constituency}->{modcom};
#        print " minister" if $consvals->{$constituency}->{minister};
#        print " MP $mp_name... ";
#    }

    if (!$dryrun) {
        $dbh->do("insert into campaigners_sent_email (campaigner_id, email_name)
                values (?, ?)", {}, $campaigner_id, $mailshot_name);
    }

    print "done\n";

    sleep 0.1; # One second probably enough
}

