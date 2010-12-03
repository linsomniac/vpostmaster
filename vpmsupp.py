#!/usr/bin/env python
#
#
# (c) 2005, 2006 tummy.com, ltd.
# vPostMaster supplemental Python scripts
#


S_revision = '$Revision: 383 $'
S_rcsid = '$Id: vpmsupp.py 383 2005-11-24 00:36:56Z jafo $'

import string, sys, os, time, syslog, re, UserDict
try: import psycopg2 as psycopg
except ImportError: import psycopg


#################
class ExceptHook:  #{{{1
	#################################################
	def __init__(self, useSyslog = 1, useStderr = 0):  #{{{2
		self.useSyslog = useSyslog
		self.useStderr = useStderr


	#######################################
	def __call__(self, etype, evalue, etb):  #{{{2
		import traceback, string
		tb = traceback.format_exception(*(etype, evalue, etb))
		tb = map(string.rstrip, tb)
		tb = string.join(tb, '\n')
		for line in string.split(tb, '\n'):
			if self.useSyslog:
				try: syslog.syslog(line)
				except: pass
			if self.useStderr:
				try: sys.stderr.write(line + '\n')
				except: pass


###############
def getDebug():
	debugFile = '/usr/lib/vpostmaster/etc/debug'
	if not os.path.exists(debugFile): return(0)
	fp = open(debugFile, 'r')
	value = fp.read(1)
	fp.close()
	try:
		value = int(value)
		if value >= 1:
			syslog.syslog(syslog.LOG_DEBUG, 'Debug level to "%d"' % value)
		return(value)
	except:
		return(0)


######################
def setupExceptHook():  #{{{1
	syslog.openlog(os.path.basename(sys.argv[0]), syslog.LOG_PID,
			syslog.LOG_MAIL)
	sys.excepthook = ExceptHook(useSyslog = 1, useStderr = 1)


########################
def getConnectStr(file):  #{{{1
	fp = open(file, 'r')
	connectStr = None
	while 1:
		line = fp.readline()
		if not line: break
		if string.find(line, 'dbname=') >= 0:
			connectStr = string.strip(line)
			break
	fp.close()

	if connectStr == None:
		raise ValueError('Unable to load connect string from file "%s"' % file)

	return(connectStr)


############################
def connectToDb(connectStr):
	try:
		from psycopg2.extras import DictConnection
		return(DictConnection(connectStr))
	except ImportError:
		import psycopg
		return(psycopg.connect(connectStr))


#########################
def dictfetchone(cursor):
	if hasattr(cursor, 'dictfetchone'):
		return(cursor.dictfetchone())
	return(cursor.fetchone())


######################
def makeMaildirName():  #{{{1
	'Return a unique maildir name.'
	hostname = os.uname()[1]
	hostname = string.replace(hostname, ':', '\\072')
	hostname = string.replace(hostname, '/', '\\057')
	now = time.time()
	return('%.6f-%s.%s' % ( now, os.getpid(), hostname ))


###########################################
class InvalidRecipientException(Exception):  #{{{1
	def __init__(self, reason):
		self.reason = reason


########################################################
class InvalidDomainException(InvalidRecipientException):  #{{{1
	def __init__(self, reason):
		self.reason = reason


##########################################
class StopRedirectionException(Exception):  #{{{1
	def __init__(self, reason):
		self.reason = reason


#############################################
def resolveDomain(domain, connection, cursor):  #{{{1
	for i in xrange(50):
		cursor.execute('SELECT * FROM domains WHERE name = %s',
				( domain, ))
		domainData = dictfetchone(cursor)
		if not domainData:					#  not a domain handled by vpostmaster
			raise InvalidDomainException('No such domain.')
		if not domainData['active']:		#  domain has been disabled
			raise InvalidRecipientException('Domain has been disabled.')

		#  deal with aliased domains  {{{3
		aliasedTo = domainData['aliasedto']
		if not aliasedTo: break
		domain = aliasedTo

	#  aliased domain loop, we reached max depth above  {{{2
	if domainData['aliasedto']:
		syslog.syslog('ERROR: Domain is an alias loop: "%s"' % recipientDomain)
		raise InvalidRecipientException('Domain is an alias loop, aborting.')
	
	return(domainData, domain)


################################
def extensionCombinations(s, e): #{{{1
	values = []
	splits = string.split(s, e)
	for i in xrange(1, len(splits) + 1):
		values.append(( string.join(splits[:i], e), string.join(splits[i:], e) ))
	return(values)


#########################################################
def resolveExtensionUser(recipientLocal, recipientDomain, #{{{1
		extensionChar, cursor):
	for local, ext in extensionCombinations(recipientLocal, extensionChar):
		if ext == '': ext = None

		#  look up the user information  {{{2
		cursor.execute('SELECT * FROM users WHERE name = %s AND domainsname = %s',
				( local, recipientDomain ))
		userData = UserDict.UserDict(dictfetchone(cursor))
		if not userData: continue

		if not userData['active']:
			if debug >= 2:
				syslog.syslog(syslog.LOG_DEBUG,
						'Raising because of inactive account')
			raise InvalidRecipientException('Account is not active.')

		return local, ext, userData

	return recipientLocal, None, None


############################################################
def resolveUser(recipientLocal, recipientDomain, loopDetect, #{{{1
		connection, cursor = None, recursionDepth = 0, debug = 0):
	#  log entry  {{{2
	if debug >= 2:
		syslog.syslog(syslog.LOG_DEBUG,
				'resolveUser(%s, %s, %s, %s)' % ( recipientLocal,
					recipientDomain, loopDetect, recursionDepth))

	#  initial setup from args  {{{2
	if cursor == None: cursor = connection.cursor()

	#  look up domain information  {{{2
	domainData, recipientDomain = \
			resolveDomain(recipientDomain, connection, cursor)
	extensionChar = domainData['extensionchar']

	recipientLocal, recipientExtension, userData = (
			resolveExtensionUser(recipientLocal, recipientDomain, extensionChar,
					cursor))

	#  try resolution, if exception just use the current data
	try:
		#  user does not exist  {{{2
		if not userData:
			if domainData['catchalladdress'] == None:
				if debug >= 2:
					syslog.syslog(syslog.LOG_DEBUG, 'Raising: No such user')
				raise InvalidRecipientException('No such user "%s" in domain "%s".'
						% ( recipientLocal, recipientDomain ))

			#  re-resolve the catchall address  {{{2
			recipientLocal = domainData['catchalladdress']
			cursor.execute(
					'SELECT * FROM users WHERE name = %s AND domainsname = %s',
					( recipientLocal, recipientDomain ))
			userData = UserDict.UserDict(dictfetchone(cursor))
			if not userData:
				if debug >= 2:
					syslog.syslog(syslog.LOG_DEBUG,
							'Raising: No such user (bad catch-all)')
				raise InvalidRecipientException(
						'No such user here (bad catch-all).')
		
		#  check for incompatible account settings  {{{2
		if userData.get('localdeliveryenabled') and userData.get('forwardto'):
			syslog.syslog(syslog.LOG_DEBUG, 'Raising because of local '
					'delivery with forwarding')
			raise StopRedirectionException('Local delivery or forwarding '
					'configured with account')

		#  check for forwarding to other domains and do internal redirect {{{2
		forwardTo = userData.get('forwardto')
		if forwardTo and len(string.split(forwardTo)) == 1:
			foo = string.split(forwardTo, '@', 1)
			if len(foo) == 1: foo = ( foo[0], recipientDomain )

			#  check domain validity
			try:
				resolveDomain(foo[1], connection, cursor)
			except InvalidDomainException:
				if debug >= 2:
					syslog.syslog(syslog.LOG_DEBUG,
							'Domain "%s" is not local, stopping redirection' % foo[1])
				raise StopRedirectionException(
						'Forwarding to a non-vPostMaster domain')

			#  check for loops  {{{3
			if loopDetect.count(forwardTo) > 0:
				if debug >= 2:
					syslog.syslog(syslog.LOG_DEBUG, 'Raising: recipient loop found.')
				raise InvalidRecipientException('Recipient loop found: %s'
						% string.join(loopDetect, ', '))
			loopDetect.append(forwardTo)

			domainData, recipientDomain, recipientLocal, recipientExtension, \
					userData, loopDetect = resolveUser(foo[0], foo[1], loopDetect,
					connection, cursor, recursionDepth + 1, debug = debug)
	except StopRedirectionException:
		pass

	#  set synthetic attributes  {{{3
	userData['_extension'] = recipientExtension

	#  return  {{{3
	if debug >= 2:
		syslog.syslog(syslog.LOG_DEBUG,
				'resolveUser returning: %s, %s, %s, %s, %s, %s' % (
					domainData, recipientDomain, recipientLocal, recipientExtension,
					userData, loopDetect ))
	return(domainData, recipientDomain, recipientLocal, recipientExtension,
			userData, loopDetect)


##############################################################
def lookupRecipient(cursor, connection, recipient, debug = 0):  #{{{1
	'''Look up the recipient in the database, raise InvalidRecipientException
	if there is a problem otherwise return a tuple of:
		( recipientLocal, recipientDomain, domainData, userData )
	'''

	#  process the recipient data  {{{2
	if not recipient:		#  recipient empty
		raise InvalidRecipientException('Recipient address empty.')
	foo = string.split(recipient, '@', 1)
	if len(foo) < 2:		#  no domain name in recipient
		raise InvalidRecipientException(
				'Recipient address does not contain domain.')
	recipientLocal, recipientDomain = foo

	domainData, recipientDomain, recipientLocal, recipientExtension, userData, \
			loopDetect = resolveUser(recipientLocal, recipientDomain, [],
					connection, cursor, debug)
	if loopDetect:
		syslog.syslog(syslog.LOG_DEBUG,
				'Message redirected via path: "%s"' % string.join(loopDetect, ', ')
				)
	
	#  look up the user settings  {{{2
	cursor.execute('SELECT * FROM usersettings WHERE usersid = %s',
			( userData['id'], ))
	while 1:
		#  get the next value
		data = cursor.fetchone()
		if not data: break

		#  translate value
		key = data[2]
		value = data[3]
		action = {
				'greylisttimeoutminutes' : int,
				'spamassassin1threshold' : float,
				'spamassassin2threshold' : float,
				'spamassassin3threshold' : float,
				}.get(key, None)
		if not value: value = None
		if value != None and action: value = action(value)

		userData[key] = value

	#  set some default values if not set
	for key in ( 'greylistaction', 'spfaction', 'spamassassin1action',
			'spamassassin2action', 'spamassassin3action', 'clamavaction',
			'addspamheaders' ):
		if userData.get(key) == None: userData[key] = 'disabled'
	if userData.get('spamsubjectprefix') == None:
		userData['spamsubjectprefix'] = ''

	#  return results
	return(( recipientLocal, recipientDomain, domainData, userData ))


#################
class checkClass:  #{{{1
	##############################
	def __init__(self, debug = 0):  #{{{2
		self.debug = debug


	##################################################################
	def runCheck(self, cursor, connection, data, domainData, userData,  #{{{2
			mode):
		'''Run the checks and return a tuple of the results: ( status, reason )
		"status" may be "OK", "REJECT", or "QUARANTINE", and "reason" is a
		string indicating why a non-OK result was returned.
		'''
		self.cursor = cursor
		self.connection = connection
		self.data = data
		self.domainData = domainData
		self.userData = userData
		self.mode = mode
		self.addHeaders = []

		#  load meta table
		cursor.execute('SELECT * FROM meta LIMIT 1')
		self.metaData = dictfetchone(cursor)
		if not self.metaData: self.metaData = {}

		#  ensure values are lower-case
		for key in ( 'sender', 'recipient', 'client_name', 'helo_name' ):
			if data.get(key): data[key] = string.lower(data[key])

		#  set up synthetic data entries
		foo = string.split(data['sender'], '@', 1)
		if len(foo) > 1: data['sender_domain'] = foo[1]

		#  run the checkers  {{{3
		for check in (
				self.checkQuota,
				self.checkEnvelope,
				self.checkSPF,
				self.checkGreylist,
				self.checkClamAV,
				self.checkSpamAssassin,
				):
			status, reason = check()

			#  log results  {{{4
			if self.debug >= 2 or ( self.debug >= 1 and status != 'DUNNO'):
				syslog.syslog(syslog.LOG_DEBUG,
						'Classifier "%s:%s" returned "%s": "%s"' %
						( self.mode, self.checkerName(check), status, reason ))

			if status != 'DUNNO':
				return(( status, reason, self.addHeaders ))

		#  successful  {{{3
		return(( 'OK', '', self.addHeaders ))


	###############################
	def checkerName(self, checker):  #{{{2
		if hasattr(checker, '__name__'):
			s = checker.__name__
			if s[:5] == 'check': s = s[5:]
			return(s)

		s = repr(checker)
		m = re.search(r'\.check(\S+)\s', s)
		if m: return(m.group(1))
		return(s)

	
	##########################
	def ruleToStr(self, rule):  #{{{2
		'''Format an envelope rule as a string.'''
		data = []
		for key, name in (
				( 'action', 'Action' ),
				( 'precedence', 'Precedence' ),
				( 'heloname', 'HELO_Name' ),
				( 'remoteip', 'Remote_IP' ),
				( 'remotename', 'Remote_Name' ),
				( 'sender', 'Sender' ),
				( 'recipient', 'Recipient' ),
				):
			value = rule.get(key)
			if value:
				data.append('%s="%s"' % ( name, value ))
		return(string.join(data, ', '))


	#####################
	def checkQuota(self):  #{{{2
		quota = self.userData['quotainmegabytes']
		if quota == None:
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkQuota: Quota disabled.')
			return(( 'DUNNO', '' ))
		#  do not do quota checking in delivery, only in the smtp
		if self.mode == 'delivery': return(( 'DUNNO', '' ))

		#  check size of quota
		userdir = self.userData['userdir']
		foundWarning = 0
		size = 0
		if self.data['size']: size = int(self.data['size'])
		stat = os.stat
		walk = os.walk
		join = os.path.join
		for root, dirs, files in walk(userdir):
			for file in files:
				if file[:19] == 'quotawarningmessage': foundWarning = 1
				try: fileInfo = stat(join(root, file))
				except OSError: continue
				size = size + fileInfo.st_size
		size = size / 1048576

		#  short-circuit if under quota
		if size < quota: return(( 'DUNNO', '' ))

		#  deliver warning message
		if not foundWarning:
			baseDir = os.path.join(self.userData['userdir'], 'Maildir')
			newFile = os.path.join(baseDir, 'new', 'quotawarningmessage')
			tmpFile = os.path.join(baseDir, 'tmp', makeMaildirName())

			#  write message
			fp = open(tmpFile, 'w')
			fp.write('From: postmaster\n')
			fp.write('To: %s\n' % self.data['recipient'])
			fp.write('Subject: WARNING: You are over quota.\n')
			fp.write('\n')
			fp.write('A message delivery was rejected because your mailbox is\n')
			fp.write('exceeding it\'s quota.  You will only receive one of\n')
			fp.write('these warning messages at a given time, but you will not\n')
			fp.write('be able to receive any further e-mail until you reduce\n')
			fp.write('your mailbox size, or raise the size quota.\n')
			fp.write('\n')
			fp.write('Your mail administrator\n')
			fp.close()

			#  deliver
			os.rename(tmpFile, newFile)

		#  reject message
		return(( 'REJECT', 'User is over quota.' ))


	########################
	def checkGreylist(self):  #{{{2
		#  return DUNNO if we don't have data we need
		if (self.data.get('client_address') == None or
				self.userData.get('greylistaction') == None):
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkGreylist: Missing action or address, skipping.')
			return(( 'DUNNO', '' ))

		#  short-circuit local messages
		if self.data['client_address'] == '127.0.0.1':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkGreylist: Message from localhost, skipping.')
			return(( 'DUNNO', '' ))

		#  check if greylisting is enabled
		action = self.userData['greylistaction']
		if action == 'disabled':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkGreylist: Greylisting disabled.')
			return(( 'DUNNO', '' ))
		#  smtp check cannot quarantine
		if self.mode == 'smtp' and action == 'quarantine':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkGreylist: SMTP cannot quarantine.')
			return(( 'DUNNO', '' ))
		#  transport check can ONLY quarantine
		if self.mode == 'delivery' and action != 'quarantine':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkGreylist: Transport can ONLY quarantine.')
			return(( 'DUNNO', '' ))
		timeout = self.userData.get('greylisttimeoutminutes', 0)

		#  check the RWL
		addrRev = string.split(self.data['client_address'], '.')
		addrRev.reverse()
		addrRev = string.join(addrRev, '.') + '.rwl.vpostmaster.org'
		try:
			import DNS
			DNS.DiscoverNameServers()
			req = DNS.DnsRequest(addrRev, qtype = 'A', timeout = 10)
			resp = req.req()
			if (resp and resp.answers
					and resp.answers[0].get('data') == '127.0.0.2'):
				if self.debug >= 3:
					syslog.syslog(syslog.LOG_DEBUG,
							'checkGreylist: Whitelisted.')
				return(( 'DUNNO', '' ))
		except:
			pass

		#  see if there's an existing record
		databaseSender = self.data['sender']
		self.cursor.execute('SELECT *, allowafter < NOW() AS allowednow, '
					'expireafter < NOW() AS expirednow, '
					'(NOW() + \'1 month\'::interval - \'5 minutes\'::interval) '
						' > expireafter as updateexpiration '
				'FROM greylist '
				'WHERE sender = %s AND recipient = %s AND remoteip = %s',
				( databaseSender, self.data['recipient'],
					self.data['client_address'] ))
		greylistData = dictfetchone(self.cursor)

		#  work around because postfix will translate a null sender
		#  into "mailer-daemon@myhostname".  If the above check failed, and
		#  the sender starts with "mailer-daemon", try "" for sender.
		if (not greylistData and self.mode == 'delivery'
				and self.data['sender'][:13] == 'mailer-daemon'):
			self.cursor.execute('SELECT *, allowafter < NOW() AS allowednow, '
						'expireafter < NOW() AS expirednow, '
						'(NOW() + \'1 month\'::interval - \'1 hour\'::interval) '
							' > expireafter as updateexpiration '
					'FROM greylist '
					'WHERE sender = %s AND recipient = %s AND remoteip = %s',
					( '', self.data['recipient'], self.data['client_address'] ))
			greylistData = dictfetchone(self.cursor)
			if greylistData: databaseSender = ''

		if self.debug >= 3:
			if greylistData:
				syslog.syslog(syslog.LOG_DEBUG,
						'DEBUG: checkGreylist select:: FOUND sender: %s, '
						'recipient: %s, client_ip: %s, now: %s, allowafter: %s, '
						'expireafter: %s'
						% ( self.data['sender'], self.data['recipient'],
						self.data['client_address'], repr(greylistData.get('now')),
						greylistData['allowafter'], greylistData['expireafter'] ))
			else:
				syslog.syslog(syslog.LOG_DEBUG,
						'DEBUG: checkGreylist select:: NOT FOUND sender: %s, '
						'recipient: %s, client_ip: %s, now: %s'
						% ( self.data['sender'], self.data['recipient'],
						self.data['client_address'], time.strftime('%x %X') ))

		#  existing greylist record, but not expired
		if greylistData and not greylistData['expirednow']:
			#  another attempt before the initial timeout
			if action != 'learn' and not greylistData['allowednow']:
				if action == 'quarantine':
					self.addHeaders.append('X-vPostMaster-Greylist: Quarantine due '
							'to existing entry before timeout.')
					return(( 'QUARANTINE', 'Greylisting: Existing entry before '
							'timeout.' ))
				else:
					return(( 'DEFER_IF_PERMIT',
							'Greylisting does not yet allow this message.' ))
			
			#  update greylist expireafter
			if greylistData.get('updateexpiration', 1):
				try:
					self.cursor.execute('UPDATE greylist '
							'SET expireafter = NOW() + \'1 month\'::interval '
							'WHERE id = %s', ( greylistData['id'], ))
					self.connection.commit()
				except ( psycopg.IntegrityError, psycopg.DatabaseError,
						psycopg.ProgrammingError):
					#  exceptions probably mean two very quick message deliveries
					pass

			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'DEBUG: checkGreylist update:: sender: %s, recipient: %s, '
						'client_ip: %s, gl timeout: %s'
						% ( self.data['sender'], self.data['recipient'],
						self.data['client_address'],
						self.userData.get('greylisttimeoutminutes', 0) ))

			#  return success
			return(( 'DUNNO', '' ))

		#  no existing record or expired
		else:
			if greylistData:
				#  expired greylist entry, remove
				if greylistData['expirednow']:
					self.cursor.execute('DELETE FROM greylist WHERE id = %s',
							( greylistData['id'], ))
					#  no commit here, we're adding a new record next
				
				#  learning mode with existing, non-expired, record
				elif action == 'learn':
					if self.debug >= 3:
						syslog.syslog(syslog.LOG_DEBUG,
								'checkGreylist: Learning.')
					return(( 'DUNNO', '' ))

			#  add record
			try:
				self.cursor.execute('INSERT INTO greylist '
						'( sender, recipient, remoteip, allowafter, expireafter ) '
						'VALUES ( %%s, %%s, %%s, NOW() + \'%s minutes\'::interval, '
							'NOW() + \'1 month\'::interval )'
								% self.userData.get('greylisttimeoutminutes', 0),
						( databaseSender, self.data['recipient'],
							self.data['client_address'] ))
				self.connection.commit()

				if self.debug >= 3:
					syslog.syslog(syslog.LOG_DEBUG,
							'DEBUG: checkGreylist insert:: sender: %s, recipient: %s, '
							'client_ip: %s, gl timeout: %s'
							% ( self.data['sender'], self.data['recipient'],
							self.data['client_address'],
							self.userData.get('greylisttimeoutminutes', 0) ))

			except ( psycopg.IntegrityError, psycopg.DatabaseError):
				#  exceptions probably mean two very quick message deliveries
				pass

			#  learning mode, do not block here 
			if action == 'learn':
				if self.debug >= 3:
					syslog.syslog(syslog.LOG_DEBUG,
							'checkGreylist: Learning.')
				return(( 'DUNNO', '' ))

			#  initial greylisting entry
			if action == 'quarantine':
				self.addHeaders.append('X-vPostMaster-Greylist: Quarantine due '
						'to new entry.')
				return(( 'QUARANTINE', 'Greylisting: New entry.' ))
			else:
				return(( 'DEFER_IF_PERMIT',
						'Greylisting detected initial message.' ))

		#  exit should be handled above
		assert 0, 'Execution should not reach here.'


	########################
	def checkEnvelope(self):  #{{{2
		if self.debug >= 2:
			syslog.syslog(syslog.LOG_DEBUG,
					'checkEnvelope(debug): client_address=%s client_name=%s '
					'sender=%s recipient=%s helo_name=%s'
					% ( self.data.get('client_address'),
						self.data.get('client_name'),
						self.data.get('sender'), self.data.get('recipient'),
						self.data.get('helo_name') ))

		#  loop optimizations
		re_search = re.search
		string_lower = string.lower
		self_cursor = self.cursor

		self.cursor.execute('SELECT * FROM enveloperules WHERE usersid = %s '
				'ORDER BY precedence DESC', ( self.userData['id'], ))
		while 1:
			rule = dictfetchone(self_cursor)
			if not rule: break

			#  check function
			def checkField(rule, data):
				#  loop optimizations
				data_get = data.get

				#  check rules
				for dbField, msgField in (
						( 'remoteip', 'client_address' ),
						( 'remotename', 'client_name' ),
						( 'sender', 'sender' ),
						( 'recipient', 'recipient' ),
						( 'heloname', 'helo_name' ),
						):
					ruleDbField = rule[dbField]
					if ruleDbField == None: continue
					dataMsgField = data_get(msgField, '')

					#  check rule
					if ruleDbField[:6] == 'regex:':
						try:
							if re_search(ruleDbField[6:], dataMsgField) == None:
								return(None)
						except Exception, e:
							syslog.syslog('ERROR: Error "%s" matching regex "%s"'
									% ( str(e), ruleDbField[6:] ))
							continue
					else:
						if ruleDbField[:7] == 'normal:':
							ruleDbField = ruleDbField[7:]
						ruleDbField = string_lower(ruleDbField)
						if dataMsgField != ruleDbField:
							return(None)
				return(rule['action'])

			#  check this rule
			ret = checkField(rule, self.data)
			if self.debug >= 2:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkEnvelope(debug): Checking rule remoteip=%s '
						'remotename=%s sender=%s recipient=%s heloname=%s: result=%s'
						% ( rule['remoteip'], rule['remotename'], rule['sender'],
						rule['recipient'], rule['heloname'], str(ret) ))
			if not ret: continue

			self.addHeaders.append('X-vPostMaster-Envelope-Match: action=%s, %s' %
					( ret, self.ruleToStr(rule) ))
			if ret == 'accept': return(( 'OK', '' ))
			if ret == 'continue': return(( 'DUNNO', '' ))
			if ret == 'reject':
				return(( 'REJECT', 'Black/white list entry, precedence %s.'
						% rule['precedence'] ))
			if ret == 'quarantine':
				#  quarantine can't be done in smtp mode
				#  return OK so that other items don't reject it
				if self.mode == 'smtp':
					if self.debug >= 3:
						syslog.syslog(syslog.LOG_DEBUG,
								'checkGreylist: Quarantine cannot be done in SMTP.')
					return(( 'OK', '' ))

				return(( 'QUARANTINE', 'Black/white list entry, precedence %s.'
						% rule['precedence'] ))

		#  no determination made from rule checking
		return(( 'DUNNO', '' ))


	###################
	def checkSPF(self):  #{{{2
		#  return DUNNO if we don't have data we need
		if (self.data.get('client_address') == None or
				self.userData.get('spfaction') == None or
				self.data.get('sender_domain') == None):
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkSPF: Do not have client, action, or sender, skipping.')
			return(( 'DUNNO', '' ))

		#  short-circuit local messages
		if self.data.get('client_address') == '127.0.0.1':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkSPF: Message from local host.')
			return(( 'DUNNO', '' ))

		#  check if SPF is disabled
		action = self.userData['spfaction']
		if action == 'disabled':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkSPF: SPF disabled.')
			return(( 'DUNNO', '' ))
		#  smtp check cannot quarantine
		if self.mode == 'smtp' and action == 'quarantine':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkSPF: SMTP cannot quarantine, skipping.')
			return(( 'DUNNO', '' ))

		#  load SPF module
		if not self.data.get('client_address'):
			return(( 'DUNNO', '' ))
		try:
			import spf
		except Exception, e:
			syslog.syslog('ERROR: Unable to import SPF module.')
			return(( 'DUNNO', '' ))

		if self.debug >= 3:
			syslog.syslog(syslog.LOG_DEBUG, 'SPF info: %s,%s,%s'
					% ( self.data['client_address'], self.data['sender_domain'],
						'unknown' ))

		#  SPF version 2 check
		if hasattr(spf, 'check2'):
			try:
				ret = spf.check2(i = self.data['client_address'],
						s = self.data['sender_domain'], h = 'unknown')
			except Exception, e:
				syslog.syslog('ERROR: SPF check failed: %s' % str(e))
				return(( 'DUNNO', '' ))
			spfResult = string.strip(ret[0])
			spfReason = string.strip(ret[1])
			spfResult = spfResult.lower()
			spfResult = spfResult.capitalize()
			if self.data.get('sender_domain'): identity = 'identity=mailfrom; '
			else: identity = 'identity=helo; '
			spfDetail = (identity + 'client-ip=%s; helo=%s; envelope-from=%s; '
					'receiver=%s; '
					% ( self.data.get('client_address', '<UNKNOWN>'),
						self.data.get('helo_name', '<UNKNOWN>'),
						self.data.get('sender', '<UNKNOWN>'),
						self.data.get('recipient', '<UNKNOWN>'),
						))

			self.addHeaders.append('X-vPostMaster-SPF-Result: '
					'domain="%s" address="%s" result="%s" reason="%s"' %
					( self.data['sender_domain'], self.data['client_address'],
						spfResult, spfReason ))
			self.addHeaders.append('Received-SPF: %s (%s) %s' % ( spfResult,
					spfReason, spfDetail ))

			if spfResult == 'Fail' or spfResult == 'Permerror':
				if action == 'quarantine':
					return(( 'QUARANTINE', 'SPF detected this as spam.' ))
				return(( 'REJECT', 'Sender failed SPF check' ))
			if spfResult == 'Temperror':
				if self.mode == 'smtp':
					return(( 'DEFER_IF_PERMIT',
							'SPF temporary failure: "%s"' % spfReason ))
				else:
					return(( 'DUNNO', '' ))

		#  SPF version 1 check
		else:
			try:
				res = spf.check(i = self.data['client_address'],
						s = self.data['sender_domain'], h = 'unknown')
			except Exception, e:
				syslog.syslog('ERROR: SPF check failed: %s' % str(e))
				return(( 'DUNNO', '' ))

			self.addHeaders.append('X-vPostMaster-SPF-Result: '
					'domain="%s" address="%s" result="%s"' %
					( self.data['sender_domain'], self.data['client_address'],
						res[0] ))
			if res[0] not in [ 'pass', 'unknown', 'error' ]:
				if self.debug >= 3:
					syslog.syslog(syslog.LOG_DEBUG,
							'checkSPF: Unknown response: "%s".' % repr(res))
				if action == 'quarantine':
					return(( 'QUARANTINE', 'SPF detected this as spam.' ))
				return(( 'REJECT', 'Sender failed SPF check' ))

		return(( 'DUNNO', '' ))


	############################
	def checkSpamAssassin(self):  #{{{2
		#  return DUNNO if we don't have data we need
		if (self.data.get('message_file') == None):
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkSpamAssassin: No message file.')
			return(( 'DUNNO', '' ))

		action1 = self.userData.get('spamassassin1action', 'disabled')
		action2 = self.userData.get('spamassassin2action', 'disabled')
		action3 = self.userData.get('spamassassin3action', 'disabled')
		if (action1 == 'disabled' and action2 == 'disabled'
				and action3 == 'disabled'):
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkSpamAssassin: All actions are disabled.')
			return(( 'DUNNO', '' ))

		#  cannot check spamassassin in smtp
		if self.mode == 'smtp':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkSpamAssassin: Cannot check in SMTP, skipping.')
			return(( 'DUNNO', '' ))

		#  run check on message
		fp = os.popen("spamc -R <'%s' 2>&1" % self.data['message_file'], 'r')
		spamScore = None
		for line in fp.readlines():
			if spamScore == None:
				m = re.match(r'^(-?[\d.]+)/[\d.]+\s*$', line)
				if m:
					spamScore = float(string.strip(m.group(1)))
					self.addHeaders.append('X-vPostMaster-SpamAssassin-Score: %s'
							% string.strip(m.group(1)))
					continue
			m = re.match(r'^\s*(-?[\d.]+)\s+(\S+)\s+(\S.*\S)\s*$', line)
			if m:
				self.addHeaders.append(
						'X-vPostMaster-SpamAssassin-Details: %s %s %s'
						% ( string.strip(m.group(1)), string.strip(m.group(2)),
							string.strip(m.group(3)) ))
		fp.close()

		#  build a list of scores/actions
		actionList = []
		valueDedupList = []
		for key, action in (
				( 'spamassassin1threshold', action1 ),
				( 'spamassassin2threshold', action2 ),
				( 'spamassassin3threshold', action3 ),
				):
			value = self.userData.get(key)
			if action == 'disabled': continue
			if not value: continue
			valueFloat = float(value)
			if valueFloat in valueDedupList: continue

			actionList.append(( valueFloat, action ))
			valueDedupList.append(valueFloat)
		actionList.sort()

		#  find action to take
		action = 'disabled'
		for score, takeAction in actionList:
			if score <= spamScore: action = takeAction
			else: break

		#  take action
		if action == 'disabled':
			return(( 'DUNNO', '' ))
		elif action == 'quarantine':
			return(( 'QUARANTINE', 'SpamAssassin detected this as spam.' ))
		elif action == 'drop':
			return(( 'DROP', 'SpamAssassin detected this as spam.' ))
		elif action == 'reject':
			return(( 'REJECT', 'SpamAssassin detected this as spam.' ))
		elif action == 'accept':
			return(( 'OK', '' ))

		#  should not reach here, it's an unknown action if it does
		raise NotImplementedError


	######################
	def checkClamAV(self):  #{{{2
		#  return DUNNO if we don't have data we need
		if (self.data.get('message_file') == None or
				self.userData.get('clamavaction') == None):
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkClamAV: No message file or action.')
			return(( 'DUNNO', '' ))

		action = self.userData['clamavaction']
		if action == 'disabled':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkClamAV: ClamAV disabled.')
			return(( 'DUNNO', '' ))

		#  cannot check clamav in smtp
		if self.mode == 'smtp':
			if self.debug >= 3:
				syslog.syslog(syslog.LOG_DEBUG,
						'checkClamAV: Cannot check in SMTP.')
			return(( 'DUNNO', '' ))

		#  figure out which clamav command to run
		clamCommand = self.metaData.get('clamscancommand', 'clamscan --stdout -')

		#  run check on message
		fp = os.popen("%s <'%s' 2>&1"
				% ( clamCommand, self.data['message_file'] ), 'r')
		ret = None
		for line in fp.readlines():
			m = re.match(r'^[^:]+:\s*(.*)\s*FOUND\s*$', line)
			if m: ret = string.strip(m.group(1))
		popenRet = fp.close()

		#  report if clamav failed to run
		if popenRet != None and popenRet != 256:
			explinationStr = ''
			if popenRet == 127 << 8:
				explinationStr = ' (Command "%s" not found)' % clamCommand

			self.addHeaders.append(
					'X-vPostMaster-ClamAV-Failure: Unable to run ClamAV: '
					'exit_status=%s signal=%s%s' % ( popenRet >> 8,
						popenRet & 0xff, explinationStr ))
			syslog.syslog('ERROR: Invoking ClamAV with "%s" '
					'failed with: exit_status=%s signal=%s%s' %
					( clamCommand, popenRet >> 8, popenRet & 0xff, explinationStr ))
			if ret == None:
				if self.debug >= 3:
					syslog.syslog(syslog.LOG_DEBUG,
							'checkClamAV: No response from ClamAV.')
				return(( 'DUNNO', '' ))

		#  no virus found
		if ret == None: return(( 'DUNNO', '' ))

		#  take action
		self.addHeaders.append(
				'X-vPostMaster-ClamAV-Match: action="%s" virus="%s"' %
				( action, ret ))
		if action == 'quarantine':
			return(( 'QUARANTINE', 'ClamAV detected a virus in this message.' ))
		elif action == 'drop':
			return(( 'DROP', 'ClamAV detected a virus in this message.' ))
		elif action == 'reject':
			return(( 'REJECT', 'ClamAV detected a virus in this message.' ))

		#  should not reach here, it's an unknown action if it does
		raise NotImplementedError
