Name:		pacifica-reporting
Epoch:		1
Version:	0.99.9
Release:	1%{?dist}
Summary:	The pacifica reporting web page
Group:		System Environment/Libraries
License:	GPLv2
URL:		http://www.example.com/
Source0:	%{name}-%{version}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch:      noarch

BuildRequires:	rsync

%description

%prep
%setup -q

%build
rm -f system index.php
mv websystem/system system
mv websystem/index.php index.php
rm -rf websystem resources

%install
mkdir -p %{buildroot}/var/www/myemsl/reporting
mkdir -p %{buildroot}/usr/lib/myemsl/apache/myemsl-ssl.d
rsync legacy-httpd.conf %{buildroot}/usr/lib/myemsl/apache/myemsl-ssl.d/reporting.conf
rsync -r application index.php system %{buildroot}/var/www/myemsl/reporting/
cp a*.png %{buildroot}/var/www/myemsl/reporting/
cp favicon*.* %{buildroot}/var/www/myemsl/reporting/
cp ms*.png %{buildroot}/var/www/myemsl/reporting/
cp safari*.svg %{buildroot}/var/www/myemsl/reporting/
cp manifest.json %{buildroot}/var/www/myemsl/reporting/
cp browserconfig.xml %{buildroot}/var/www/myemsl/reporting/
mkdir -p %{buildroot}/var/www/myemsl/reporting/application/logs

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
/var/www/myemsl/reporting
/usr/lib/myemsl/apache/myemsl-ssl.d/reporting.conf
%defattr(-,apache,apache,-)
/var/www/myemsl/reporting/application/logs

%changelog
* Mon Mar 21 2016 David Brown <david.brown@pnnl.gov> 0.99.7-1
- Initial RHEL release.
