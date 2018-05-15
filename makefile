
.PHONY: publish remove

publish:
	git add .
	git tag v$(v)
	git checkout -b $(v) v$(v)
	git commit -m "$(m)"
	git push origin $(v)
	git push --tag
	git checkout master
	git merge $(v)
	git push

remove:
	git checkout master
	git branch -d $(v)
	git tag -d v$(v)
